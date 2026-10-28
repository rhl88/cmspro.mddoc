<?php

namespace App\Apps\CmsproMddoc\Tests;

use App\Apps\CmsproMddoc\ServiceProvider;
use App\Models\AdminRole;
use App\Models\AdminUser;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Markdown 文档应用测试基类
 *
 * 本应用为纯文件系统应用（无数据库表），测试要点：
 * - 补建 admin_users / admin_roles / admin_user_roles 最小表（auth:admin 认证 + 权限判定依赖）
 * - 手动注册应用 ServiceProvider 以加载前后台路由与视图
 * - Storage::fake('local') + 独立 storage_path 配置，隔离真实 storage/app/mddoc 文档目录
 * - 测试层禁用 CSRF 中间件（后台路由带 web 中间件，POST/DELETE 需要 token，
 *   系统全局 CSRF 豁免仅覆盖 admin 段下含 api 的通配路径，不匹配本应用路由）
 */
abstract class MddocTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTables();

        // 注册应用 ServiceProvider（应用已 booted，register() 会立即执行其 boot()）
        $this->app->register(ServiceProvider::class);
        app('router')->getRoutes()->refreshNameLookups();

        // 隔离真实文档目录
        Storage::fake('local');
        config(['apps.cmspro.mddoc.storage_path' => 'mddoc-test']);

        // 测试层豁免 CSRF
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    /**
     * 补建后台认证相关最小表（结构对齐 public/install.sql）
     */
    protected function createAdminTables(): void
    {
        if (!Schema::hasTable('admin_users')) {
            Schema::create('admin_users', function ($table) {
                $table->bigIncrements('id');
                $table->string('username', 50)->unique();
                $table->string('password', 255);
                $table->string('name', 50)->nullable();
                $table->string('email', 100)->nullable();
                $table->string('phone', 20)->nullable();
                $table->string('avatar', 255)->nullable();
                $table->unsignedTinyInteger('status')->default(1);
                $table->timestamp('last_login_at')->nullable();
                $table->string('last_login_ip', 45)->nullable();
                $table->unsignedInteger('login_count')->default(0);
                $table->timestamp('password_changed_at')->nullable();
                $table->timestamp('create_time')->nullable();
                $table->timestamp('update_time')->nullable();
            });
        }

        if (!Schema::hasTable('admin_roles')) {
            Schema::create('admin_roles', function ($table) {
                $table->bigIncrements('id');
                $table->string('name', 50);
                $table->string('code', 50)->unique();
                $table->string('description', 255)->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->unsignedInteger('sort')->default(0);
                $table->unsignedTinyInteger('status')->default(1);
                $table->timestamp('create_time')->nullable();
                $table->timestamp('update_time')->nullable();
            });
        }

        if (!Schema::hasTable('admin_user_roles')) {
            Schema::create('admin_user_roles', function ($table) {
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('role_id');
                $table->timestamp('create_time')->nullable();
                $table->timestamp('update_time')->nullable();
            });
        }
    }

    /**
     * 创建超级管理员并登录 admin guard（hasPermission 直接放行）
     */
    protected function loginAsAdmin(): AdminUser
    {
        $role = AdminRole::create([
            'name' => '超级管理员',
            'code' => 'super_admin',
            'status' => 1,
        ]);

        $user = AdminUser::create([
            'username' => 'admin',
            'password' => bcrypt('password'),
            'name' => '管理员',
            'status' => 1,
        ]);

        $user->roles()->attach($role->id);

        $this->actingAs($user, 'admin');

        return $user;
    }

    /**
     * 创建无任何角色的普通管理员（hasPermission 返回 false，用于 403 场景）
     */
    protected function loginAsLimitedAdmin(): AdminUser
    {
        $user = AdminUser::create([
            'username' => 'editor_' . uniqid(),
            'password' => bcrypt('password'),
            'name' => '编辑',
            'status' => 1,
        ]);

        $this->actingAs($user, 'admin');

        return $user;
    }

    /**
     * 在隔离存储中写入一份 Markdown 文档
     */
    protected function seedDoc(string $relativePath, string $content): void
    {
        Storage::disk('local')->put('mddoc-test/' . $relativePath, $content);
    }

    /**
     * 补建系统配置表（结构对齐 public/install.sql 的 config_groups / config_items）
     */
    protected function createConfigTables(): void
    {
        if (!Schema::hasTable('config_groups')) {
            Schema::create('config_groups', function ($table) {
                $table->bigIncrements('id');
                $table->string('name', 50);
                $table->string('code', 50)->unique();
                $table->string('app_id', 50)->nullable();
                $table->unsignedInteger('sort')->default(0);
                $table->unsignedTinyInteger('status')->default(1);
                $table->timestamp('create_time')->nullable();
                $table->timestamp('update_time')->nullable();
            });
        }

        if (!Schema::hasTable('config_items')) {
            Schema::create('config_items', function ($table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('group_id')->nullable();
                $table->string('name', 100)->default('');
                $table->string('code', 150)->unique();
                $table->text('value')->nullable();
                $table->string('type', 20)->default('text');
                $table->text('options')->nullable();
                $table->text('tips')->nullable();
                $table->integer('sort')->default(0);
                $table->unsignedTinyInteger('status')->default(1);
                $table->timestamp('create_time')->nullable();
                $table->timestamp('update_time')->nullable();
            });
        }
    }

    /**
     * 写入前台品牌配置项（config_groups.brand 分组，code 规则与框架 registerConfigGroups 一致）
     *
     * @param array<string, string> $values 键为 logo/name/link；logo 为上传文件名（如 brand_icon.png），空为默认图标
     */
    protected function seedBrandConfig(array $values): void
    {
        $this->createConfigTables();

        $group = \App\Models\ConfigGroup::create([
            'name' => '前台品牌设置',
            'code' => 'app_cmspro_mddoc_brand',
            'app_id' => 'cmspro.mddoc',
            'status' => 1,
        ]);

        $codes = [
            'logo' => 'app_cmspro_mddoc_brand_logo',
            'name' => 'app_cmspro_mddoc_brand_name',
            'link' => 'app_cmspro_mddoc_brand_link',
        ];

        foreach ($values as $key => $value) {
            \App\Models\ConfigItem::create([
                'group_id' => $group->id,
                'name' => $key,
                'code' => $codes[$key],
                'value' => $value,
                'type' => 'text',
                'status' => 1,
            ]);
        }
    }
}
