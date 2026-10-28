<?php

namespace App\Apps\CmsproMddoc\Services;

use App\Models\ConfigGroup;
use App\Models\ConfigItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * 前台品牌配置服务（对应 manifest.json config_groups.brand）
 *
 * 品牌名称与链接存于 config_items 表；品牌图标为上传文件（默认无上传时使用内置 SVG），
 * 文件存于 storage/app/{ICON_DIR}/brand_icon.{ext}，config_items 中仅记录文件名。
 */
class BrandService
{
    /** 上传图标存储目录（storage/app/ 下相对目录） */
    public const ICON_DIR = 'mddoc-brand';

    /** 上传图标允许的扩展名 */
    public const ICON_EXTENSIONS = ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'];

    /** 配置项键名 → config_items code */
    private const CONFIG_KEYS = [
        'logo' => 'app_cmspro_mddoc_brand_logo',
        'name' => 'app_cmspro_mddoc_brand_name',
        'link' => 'app_cmspro_mddoc_brand_link',
        'navs' => 'app_cmspro_mddoc_brand_navs',
    ];

    /** 配置项中文标题（保存时写入 config_items.name） */
    private const LABELS = [
        'logo' => '品牌图标（上传文件名，空为默认图标）',
        'name' => '品牌名称',
        'link' => '品牌链接',
        'navs' => '前台导航菜单（JSON）',
    ];

    /** 默认前台导航菜单（未配置时使用） */
    private const DEFAULT_NAVS = [
        ['title' => '首页', 'url' => '/'],
        ['title' => '应用市场', 'url' => '/apps'],
        ['title' => '帮助中心', 'url' => '/help'],
    ];

    /** 默认品牌名称与链接（未配置或留空时使用，与 manifest.json 默认值一致） */
    private const DEFAULTS = [
        'name' => '文档中心',
        'link' => '/',
    ];

    /** 默认品牌图标（内置 SVG，未上传图标时使用） */
    private const DEFAULT_SVG = '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 2l5 5h-5V4zM6 20V4h6v6h6v10H6z"/></svg>';

    private const GROUP_CODE = 'app_cmspro_mddoc_brand';

    /**
     * 读取原始配置值（供后台设置表单回显；logo 为上传文件名，未上传为空字符串）
     */
    public function values(): array
    {
        try {
            $items = ConfigItem::whereIn('code', array_values(self::CONFIG_KEYS))
                ->pluck('value', 'code');
        } catch (\Throwable) {
            return ['logo' => '', 'name' => '', 'link' => ''];
        }

        // 兼容旧版 SVG 代码配置：非 brand_icon.{ext} 格式的值视为未上传
        $logo = (string) $items->get(self::CONFIG_KEYS['logo'], '');
        if (!preg_match('/^brand_icon\.(' . implode('|', self::ICON_EXTENSIONS) . ')$/i', $logo)) {
            $logo = '';
        }

        return [
            'logo' => $logo,
            'name' => (string) $items->get(self::CONFIG_KEYS['name'], ''),
            'link' => (string) $items->get(self::CONFIG_KEYS['link'], ''),
        ];
    }

    /**
     * 读取渲染用配置（前台使用）：icon_url 与 icon_svg 二选一（有上传文件时用 URL）
     *
     * @return array{icon_url: ?string, icon_svg: ?string, name: string, link: string}
     */
    public function resolved(): array
    {
        $defaults = self::DEFAULTS;

        $config = [
            'icon_url' => null,
            'icon_svg' => self::DEFAULT_SVG,
            'name' => $defaults['name'],
            'link' => $defaults['link'],
        ];

        try {
            $items = ConfigItem::whereIn('code', array_values(self::CONFIG_KEYS))
                ->pluck('value', 'code');

            if ($file = $this->uploadedIconFile((string) $items->get(self::CONFIG_KEYS['logo'], ''))) {
                $config['icon_url'] = '/api/mddoc/brand-icon?v=' . filemtime($file);
            }

            $name = trim((string) $items->get(self::CONFIG_KEYS['name'], ''));
            $config['name'] = $name !== '' ? $name : $defaults['name'];

            $link = trim((string) $items->get(self::CONFIG_KEYS['link'], ''));
            $config['link'] = $link !== '' ? $link : $defaults['link'];
        } catch (\Throwable) {
            // 配置表缺失/查询异常：整体使用默认值，保证前台可用
        }

        // 前台导航菜单（navs() 自带异常兜底，渲染所需键在此统一聚合）
        $config['navs'] = $this->navs();

        return $config;
    }

    /**
     * 读取前台导航菜单（title/url 数组；未配置或解析异常时回退默认三项）
     *
     * @return array<int, array{title: string, url: string}>
     */
    public function navs(): array
    {
        try {
            $raw = ConfigItem::where('code', self::CONFIG_KEYS['navs'])->value('value');
        } catch (\Throwable) {
            return self::DEFAULT_NAVS;
        }

        if ($raw === null || trim((string) $raw) === '') {
            return self::DEFAULT_NAVS;
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return self::DEFAULT_NAVS;
        }

        $navs = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) continue;
            $title = trim((string) ($item['title'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));
            if ($title === '' || $url === '') continue;
            $navs[] = ['title' => $title, 'url' => $url];
        }

        return $navs;
    }

    /**
     * 保存前台导航菜单（幂等；空数组时清除配置恢复默认）
     *
     * @param array<int, array{title: string, url: string}> $navs
     */
    public function saveNavs(array $navs): void
    {
        $json = json_encode(array_values($navs), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        DB::transaction(function () use ($json) {
            ConfigItem::updateOrCreate(
                ['code' => self::CONFIG_KEYS['navs']],
                [
                    'group_id' => $this->ensureGroup()->id,
                    'name' => self::LABELS['navs'],
                    'value' => $json ?: '',
                    'type' => 'textarea',
                    'sort' => 3,
                    'status' => 1,
                ]
            );
        });
    }

    /**
     * 保存品牌名称与链接配置（幂等）
     *
     * @param array{name?: string, link?: string} $values
     */
    public function save(array $values): void
    {
        DB::transaction(function () use ($values) {
            $groupId = $this->ensureGroup()->id;

            foreach (['name', 'link'] as $key) {
                ConfigItem::updateOrCreate(
                    ['code' => self::CONFIG_KEYS[$key]],
                    [
                        'group_id' => $groupId,
                        'name' => self::LABELS[$key],
                        'value' => trim((string) ($values[$key] ?? '')),
                        'type' => 'text',
                        'sort' => array_search($key, array_keys(self::CONFIG_KEYS)),
                        'status' => 1,
                    ]
                );
            }
        });
    }

    /**
     * 保存上传的品牌图标：以 brand_icon.{ext} 存储并记录到配置，替换时清理旧扩展名文件
     *
     * @return string 存储后的文件名（如 brand_icon.png）
     */
    public function saveIcon(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $fileName = 'brand_icon.' . $ext;

        // 清理其他扩展名的旧图标，避免残留
        foreach (self::ICON_EXTENSIONS as $allowedExt) {
            if ($allowedExt !== $ext) {
                Storage::disk('local')->delete(self::ICON_DIR . '/brand_icon.' . $allowedExt);
            }
        }

        $file->storeAs(self::ICON_DIR, $fileName, 'local');
        $this->writeLogoConfig($fileName);

        return $fileName;
    }

    /**
     * 恢复默认图标：删除上传文件并清空配置
     */
    public function resetIcon(): void
    {
        foreach (self::ICON_EXTENSIONS as $ext) {
            Storage::disk('local')->delete(self::ICON_DIR . '/brand_icon.' . $ext);
        }
        $this->writeLogoConfig('');
    }

    /**
     * 获取当前上传图标的绝对路径（供路由响应文件），无上传文件时返回 null
     */
    public function uploadedIconPath(): ?string
    {
        $fileName = $this->values()['logo'];

        return $this->uploadedIconFile($fileName);
    }

    /**
     * 校验记录的文件名并返回其绝对路径（仅接受 brand_icon.{允许扩展名} 格式，保证旧版 SVG 配置值被忽略）
     */
    private function uploadedIconFile(string $fileName): ?string
    {
        if (!preg_match('/^brand_icon\.(' . implode('|', self::ICON_EXTENSIONS) . ')$/i', $fileName)) {
            return null;
        }

        $disk = Storage::disk('local');
        $relative = self::ICON_DIR . '/' . $fileName;

        if (!$disk->exists($relative)) {
            return null;
        }

        return $disk->path($relative);
    }

    /**
     * 写入品牌图标配置（幂等）
     */
    private function writeLogoConfig(string $fileName): void
    {
        DB::transaction(function () use ($fileName) {
            ConfigItem::updateOrCreate(
                ['code' => self::CONFIG_KEYS['logo']],
                [
                    'group_id' => $this->ensureGroup()->id,
                    'name' => self::LABELS['logo'],
                    'value' => $fileName,
                    'type' => 'text',
                    'sort' => 0,
                    'status' => 1,
                ]
            );
        });
    }

    /**
     * 确保品牌配置分组存在并返回
     */
    private function ensureGroup(): ConfigGroup
    {
        return ConfigGroup::updateOrCreate(
            ['code' => self::GROUP_CODE],
            [
                'name' => '前台品牌设置',
                'app_id' => 'cmspro.mddoc',
                'sort' => 0,
                'status' => 1,
            ]
        );
    }
}
