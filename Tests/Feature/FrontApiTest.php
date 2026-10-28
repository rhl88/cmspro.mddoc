<?php

namespace App\Apps\CmsproMddoc\Tests\Feature;

use App\Apps\CmsproMddoc\Tests\MddocTestCase;
use Illuminate\Support\Facades\Storage;

/**
 * 前台公开 API 功能测试
 *
 * 覆盖：structure 分类结构（根目录归入基础文档、README 排前、
 * 中文目录 ID 唯一）、file 内容读取（非 .md / 不存在 404）、
 * download 附件响应。
 */
class FrontApiTest extends MddocTestCase
{
    // ---------- structure ----------

    public function test_structure_returns_categories(): void
    {
        $this->seedDoc('README.md', '# 文档中心');
        $this->seedDoc('说明.md', '# 站点说明');
        $this->seedDoc('入门指南/01-快速开始.md', '# 快速开始');
        $this->seedDoc('开发文档/后端规范.md', '# 后端规范');

        $response = $this->getJson('/api/mddoc/structure')
            ->assertOk()
            ->assertJsonPath('code', 0);

        $categories = $response->json('data.categories');

        // 根目录文件归入「基础文档」，README 排最前
        $basic = $categories[0];
        $this->assertSame('基础文档', $basic['name']);
        $this->assertSame('README.md', $basic['files'][0]['file']);
        $this->assertCount(2, $basic['files']);

        // 子目录各为一个分类
        $names = array_column(array_slice($categories, 1), 'name');
        $this->assertContains('入门指南', $names);
        $this->assertContains('开发文档', $names);
    }

    public function test_structure_category_ids_unique_for_chinese_dirs(): void
    {
        // 两个纯中文目录（同字数）必须生成不同 ID
        $this->seedDoc('入门指南/01-快速开始.md', '# 快速开始');
        $this->seedDoc('进阶指南/01-进阶配置.md', '# 进阶配置');

        $categories = $this->getJson('/api/mddoc/structure')
            ->json('data.categories');

        // 无根目录文件时不存在 basic 分类，两个子目录分类直接返回
        $ids = array_column($categories, 'id');
        $this->assertCount(2, $ids);
        $this->assertCount(2, array_unique($ids));
    }

    public function test_structure_empty_when_no_docs(): void
    {
        $this->getJson('/api/mddoc/structure')
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.categories', []);
    }

    // ---------- file ----------

    public function test_file_returns_content(): void
    {
        $this->seedDoc('入门指南/01-快速开始.md', '# 快速开始');

        $this->getJson('/api/mddoc/file?path=' . urlencode('入门指南/01-快速开始.md'))
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.content', '# 快速开始');
    }

    public function test_file_rejects_non_md(): void
    {
        $this->seedDoc('notes.txt', '纯文本');

        $this->getJson('/api/mddoc/file?path=' . urlencode('notes.txt'))
            ->assertStatus(404);
    }

    public function test_file_rejects_path_traversal(): void
    {
        $this->getJson('/api/mddoc/file?path=' . urlencode('../app.php'))
            ->assertStatus(404);
    }

    public function test_file_missing_returns_404(): void
    {
        $this->getJson('/api/mddoc/file?path=' . urlencode('不存在.md'))
            ->assertStatus(404);
    }

    // ---------- download ----------

    public function test_download_returns_markdown_attachment(): void
    {
        $this->seedDoc('README.md', '# 文档中心');

        $response = $this->get('/api/mddoc/download?path=' . urlencode('README.md'))
            ->assertOk();

        $this->assertStringContainsString('text/markdown', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertSame('# 文档中心', $response->getContent());
    }

    // ---------- 前台品牌配置 ----------

    public function test_home_renders_default_brand_without_config(): void
    {
        $response = $this->get('/docs');

        $response->assertOk()
            ->assertSee('文档中心', false)
            ->assertSee('href="/" class="brand"', false);
    }

    public function test_home_applies_brand_config(): void
    {
        $this->seedBrandConfig([
            'name' => '帮助手册',
            'link' => '/help',
        ]);

        $response = $this->get('/docs');

        // 未上传图标时使用默认内置 SVG，名称与链接按配置输出
        $response->assertOk()
            ->assertSee('帮助手册', false)
            ->assertSee('href="/help" class="brand"', false)
            ->assertSee('brand-logo', false);
    }

    public function test_home_uses_uploaded_icon(): void
    {
        $this->seedBrandConfig([
            'logo' => 'brand_icon.png',
        ]);
        Storage::disk('local')->put('mddoc-brand/brand_icon.png', 'fake-png');

        $response = $this->get('/docs');

        $response->assertOk()
            ->assertSee('<span class="brand-logo"><img src="/api/mddoc/brand-icon?v=', false);
    }

    public function test_home_renders_default_navs_without_config(): void
    {
        // 未配置导航时渲染默认三项菜单
        $this->get('/docs')
            ->assertOk()
            ->assertSee('>首页</a>', false)
            ->assertSee('>应用市场</a>', false)
            ->assertSee('>帮助中心</a>', false);
    }

    public function test_brand_icon_returns_404_without_upload(): void
    {
        $this->createConfigTables();

        $this->get('/api/mddoc/brand-icon')->assertNotFound();
    }

    public function test_home_falls_back_to_default_when_config_blank(): void
    {
        $this->seedBrandConfig([
            'logo' => '',
            'name' => '',
            'link' => '',
        ]);

        $response = $this->get('/docs');

        $response->assertOk()
            ->assertSee('文档中心', false);
    }
}
