<?php

namespace App\Apps\CmsproMddoc\Tests\Feature;

use App\Apps\CmsproMddoc\Tests\MddocTestCase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;

/**
 * 后台文档管理 API 功能测试
 *
 * 覆盖：认证拦截、权限校验（403）、目录/文件 CRUD 正常流、
 * 路径穿越拒绝、重复操作失败、删除非空目录拒绝、文件上传。
 */
class AdminDocManageApiTest extends MddocTestCase
{
    // ---------- 认证与页面 ----------

    public function test_guest_cannot_access_admin_page(): void
    {
        $this->get('/admin/cmspro/mddoc')->assertRedirect();
    }

    public function test_admin_page_renders(): void
    {
        $this->loginAsAdmin();

        $this->get('/admin/cmspro/mddoc')
            ->assertOk()
            ->assertSee('MD文档管理')
            ->assertSee('md-toolbar')
            ->assertSee('md-preview');
    }

    // ---------- 应用设置（前台品牌配置） ----------

    public function test_settings_page_renders(): void
    {
        $this->loginAsAdmin();

        $this->get('/admin/cmspro/mddoc/settings')
            ->assertOk()
            ->assertSee('应用设置')
            ->assertSee('品牌图标')
            ->assertSee('icon-preview');
    }

    public function test_save_settings_persists_and_redisplays(): void
    {
        $this->loginAsAdmin();
        $this->createConfigTables();

        $this->postJson('/admin/cmspro/mddoc/settings', [
            'brand_name' => '产品手册',
            'brand_link' => '/manual',
        ])->assertOk()->assertJsonPath('code', 0);

        // 配置落盘
        $this->assertDatabaseHas('config_items', [
            'code' => 'app_cmspro_mddoc_brand_name',
            'value' => '产品手册',
        ]);
        $this->assertDatabaseHas('config_items', [
            'code' => 'app_cmspro_mddoc_brand_link',
            'value' => '/manual',
        ]);

        // 设置页回显
        $this->get('/admin/cmspro/mddoc/settings')
            ->assertOk()
            ->assertSee('产品手册');

        // 前台生效
        $this->get('/docs')
            ->assertOk()
            ->assertSee('href="/manual" class="brand"', false)
            ->assertSee('产品手册', false);
    }

    public function test_upload_icon_persists_and_served(): void
    {
        $this->loginAsAdmin();
        $this->createConfigTables();

        $this->post('/admin/cmspro/mddoc/settings/icon', [
            'icon' => \Illuminate\Http\Testing\File::fake()->image('logo.png'),
        ], ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJsonPath('code', 0);

        // 配置与文件落盘
        $this->assertDatabaseHas('config_items', [
            'code' => 'app_cmspro_mddoc_brand_logo',
            'value' => 'brand_icon.png',
        ]);
        Storage::disk('local')->assertExists('mddoc-brand/brand_icon.png');

        // 图标接口可访问
        $this->get('/api/mddoc/brand-icon')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        // 前台使用上传图标
        $this->get('/docs')
            ->assertOk()
            ->assertSee('brand-icon', false);
    }

    public function test_upload_icon_rejects_invalid_type(): void
    {
        $this->loginAsAdmin();
        $this->createConfigTables();

        $this->post('/admin/cmspro/mddoc/settings/icon', [
            'icon' => \Illuminate\Http\Testing\File::fake()->createWithContent('evil.txt', 'plain text, not an image'),
        ], ['Accept' => 'application/json'])->assertStatus(422);
    }

    public function test_reset_icon_restores_default(): void
    {
        $this->loginAsAdmin();
        $this->createConfigTables();

        $this->post('/admin/cmspro/mddoc/settings/icon', [
            'icon' => \Illuminate\Http\Testing\File::fake()->image('logo.png'),
        ])->assertOk();

        $this->postJson('/admin/cmspro/mddoc/settings/icon/reset')
            ->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('config_items', [
            'code' => 'app_cmspro_mddoc_brand_logo',
            'value' => '',
        ]);
        Storage::disk('local')->assertMissing('mddoc-brand/brand_icon.png');
        $this->get('/api/mddoc/brand-icon')->assertNotFound();
    }

    public function test_save_settings_validates_length(): void
    {
        $this->loginAsAdmin();
        $this->createConfigTables();

        $this->postJson('/admin/cmspro/mddoc/settings', [
            'brand_name' => str_repeat('超', 60),
        ])->assertStatus(422);
    }

    public function test_save_navs_persists_and_front_applies(): void
    {
        $this->loginAsAdmin();
        $this->createConfigTables();

        $this->postJson('/admin/cmspro/mddoc/settings/navs', [
            'navs' => [
                ['title' => '首页', 'url' => '/'],
                ['title' => '开发文档', 'url' => '/dev-docs'],
            ],
        ])->assertOk()->assertJsonPath('code', 0);

        // 配置以 JSON 落盘
        $this->assertDatabaseHas('config_items', [
            'code' => 'app_cmspro_mddoc_brand_navs',
            'value' => '[{"title":"首页","url":"/"},{"title":"开发文档","url":"/dev-docs"}]',
        ]);

        // 前台渲染新菜单
        $this->get('/docs')
            ->assertOk()
            ->assertSee('开发文档', false)
            ->assertSee('href="/dev-docs"', false);
    }

    public function test_save_navs_validates_items(): void
    {
        $this->loginAsAdmin();
        $this->createConfigTables();

        // 超过 10 项
        $navs = [];
        for ($i = 0; $i < 11; $i++) {
            $navs[] = ['title' => '菜单' . $i, 'url' => '/nav-' . $i];
        }
        $this->postJson('/admin/cmspro/mddoc/settings/navs', ['navs' => $navs])
            ->assertStatus(422);

        // 缺少标题
        $this->postJson('/admin/cmspro/mddoc/settings/navs', [
            'navs' => [['title' => '', 'url' => '/x']],
        ])->assertStatus(422);
    }

    public function test_guest_cannot_access_settings_page(): void
    {
        $this->get('/admin/cmspro/mddoc/settings')->assertRedirect();
    }

    // ---------- 目录操作 ----------

    public function test_create_directory_success_and_duplicate_rejected(): void
    {
        $this->loginAsAdmin();

        $this->postJson('/admin/cmspro/mddoc/directory', ['path' => '开发文档'])
            ->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('local')->exists('mddoc-test/开发文档'));

        // 重复创建失败
        $this->postJson('/admin/cmspro/mddoc/directory', ['path' => '开发文档'])
            ->assertOk()
            ->assertJsonPath('code', 1);
    }

    public function test_create_directory_requires_permission(): void
    {
        $this->loginAsLimitedAdmin();

        $this->postJson('/admin/cmspro/mddoc/directory', ['path' => '无权限目录'])
            ->assertStatus(403);
    }

    public function test_delete_nonempty_directory_rejected(): void
    {
        $this->loginAsAdmin();
        $this->seedDoc('入门指南/01-快速开始.md', '# 快速开始');

        $this->deleteJson('/admin/cmspro/mddoc/directory', ['path' => '入门指南'])
            ->assertOk()
            ->assertJsonPath('code', 1);

        // 文件仍在
        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('local')->exists('mddoc-test/入门指南/01-快速开始.md'));
    }

    // ---------- 文档创建 / 读取 / 保存 ----------

    public function test_upload_creates_document(): void
    {
        $this->loginAsAdmin();

        $this->postJson('/admin/cmspro/mddoc/upload', [
            'path' => '开发文档/API规范.md',
            'content' => '# API 规范',
        ])->assertOk()->assertJsonPath('code', 0);

        $this->assertSame('# API 规范', \Illuminate\Support\Facades\Storage::disk('local')->get('mddoc-test/开发文档/API规范.md'));
    }

    public function test_upload_requires_permission(): void
    {
        $this->loginAsLimitedAdmin();

        $this->postJson('/admin/cmspro/mddoc/upload', [
            'path' => 'test.md',
            'content' => '# test',
        ])->assertStatus(403);
    }

    public function test_upload_rejects_path_traversal(): void
    {
        $this->loginAsAdmin();

        $this->postJson('/admin/cmspro/mddoc/upload', [
            'path' => '../evil.md',
            'content' => 'malicious',
        ])->assertOk()->assertJsonPath('code', 1);

        $this->assertFalse(\Illuminate\Support\Facades\Storage::disk('local')->exists('evil.md'));
    }

    public function test_read_file_returns_content(): void
    {
        $this->loginAsAdmin();
        $this->seedDoc('guide.md', '# 指南内容');

        $this->getJson('/admin/cmspro/mddoc/read?path=' . urlencode('guide.md'))
            ->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.content', '# 指南内容')
            ->assertJsonPath('data.path', 'guide.md');
    }

    public function test_read_missing_file_returns_404(): void
    {
        $this->loginAsAdmin();

        $this->getJson('/admin/cmspro/mddoc/read?path=' . urlencode('不存在.md'))
            ->assertStatus(404);
    }

    // ---------- 重命名 ----------

    public function test_rename_file_success(): void
    {
        $this->loginAsAdmin();
        $this->seedDoc('旧名.md', '# 内容');

        $this->postJson('/admin/cmspro/mddoc/rename', [
            'old_path' => '旧名.md',
            'new_name' => '新名',
        ])->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.new_path', '新名.md');

        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('local')->exists('mddoc-test/新名.md'));
        $this->assertFalse(\Illuminate\Support\Facades\Storage::disk('local')->exists('mddoc-test/旧名.md'));
    }

    // ---------- 删除 ----------

    public function test_delete_file_success(): void
    {
        $this->loginAsAdmin();
        $this->seedDoc('待删除.md', '# 内容');

        $this->deleteJson('/admin/cmspro/mddoc/file', ['path' => '待删除.md'])
            ->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertFalse(\Illuminate\Support\Facades\Storage::disk('local')->exists('mddoc-test/待删除.md'));
    }

    public function test_delete_file_requires_permission(): void
    {
        $this->loginAsLimitedAdmin();
        $this->seedDoc('locked.md', '# 内容');

        $this->deleteJson('/admin/cmspro/mddoc/file', ['path' => 'locked.md'])
            ->assertStatus(403);

        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('local')->exists('mddoc-test/locked.md'));
    }

    // ---------- 文件上传 ----------

    public function test_upload_md_file(): void
    {
        $this->loginAsAdmin();

        $uploaded = File::fake()->createWithContent('接口说明.txt', "# 接口说明\n\n内容");

        $this->postJson('/admin/cmspro/mddoc/upload-file', [
            'file' => $uploaded,
            'directory' => '入门指南',
        ])->assertOk()->assertJsonPath('code', 0);

        // 自动补 .md 后缀
        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('local')->exists('mddoc-test/入门指南/接口说明.txt.md'));
    }
}
