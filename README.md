# Markdown文档 应用文档

> 应用ID：`cmspro.mddoc` | 文档版本：1.0.0 | 应用版本：1.1.0 | 依赖：PHP ≥ 8.3, CmsPro ≥ 5.0.0

- 官方地址：https://www.cmspro.cn/apps/cmspro.mddoc
- 本应用提供在线 Markdown 文档中心，基于 CMSPRO 运行，请先安装 [CMSPRO](https://www.cmspro.cn/help/2)

## 1. 应用概述

将 Markdown 文件按文件夹组织，在前台以「左侧分类导航 + 中间文档内容 + 右侧本页目录」三栏布局展示，支持全文搜索、代码高亮与亮暗主题，适合团队文档中心、产品帮助文档、知识库等场景。文档内容直接存放在服务器本地磁盘，无数据库表，部署与运维成本低。

- **文档组织**：多级文件夹分类、根目录文档自动归入「基础文档」、后台目录树浏览与新建/重命名/删除目录、上传 `.md` 文件（≤10MB，自动补扩展名）
- **阅读体验**：marked 渲染、highlight.js 代码高亮（语言标签/折叠/一键复制）、面包屑、上一篇/下一篇导航、本页目录滚动高亮、移动端抽屉式导航、亮暗主题切换并记忆偏好
- **检索与导出**：前端全文搜索（`/` 快捷键聚焦、结果关键字高亮）、文档原始 Markdown 下载、`#/doc/{路径}` 锚点直达、结构与搜索索引前端缓存 5 分钟
- **后台编辑**：分屏「源码 + 实时预览」、格式工具栏（标题/加粗/斜体/代码/链接/图片/列表/表格/引用/分割线）、`Ctrl+S` 快捷保存、三种文档模板（空白/标准/接口）新建文档
- **品牌与导航**：品牌图标（上传 PNG/JPG/GIF/SVG/WEBP，未上传用内置默认图标）、品牌名称、品牌链接；前台顶栏导航菜单可自定义（最多 10 项，清空恢复默认）
- **权限控制**：声明 `cmspro.mddoc.manage`（文档管理）、`cmspro.mddoc.upload`（上传文档）、`cmspro.mddoc.delete`（删除文档）三项权限
- **存储方式**：纯文件系统，文档写入 Laravel `local` 磁盘的 `mddoc` 目录（`storage/app/private/mddoc/`），不使用数据库表

## 2. 应用截图

> 截图来源：[官方应用页 · cmspro.mddoc](https://www.cmspro.cn/apps/cmspro.mddoc)（官网标注 v1.1.0）。每次发布与更新必须核对本节截图与官网「应用截图」是否一致，不一致时以官网为准同步更新。

![应用截图 1](https://www.cmspro.cn/uploads/2026/10/05/8gpDltcSm7e2VxKcljHzJKWFXB680x6bEgHCPeAG.png)

![应用截图 2](https://www.cmspro.cn/uploads/2026/10/05/vrk5pkh8QbJF8wGd59yPB82Mtb94fsPhnSI3tf2H.png)

![应用截图 3](https://www.cmspro.cn/uploads/2026/10/05/T77gXXdzUYcJhpFLiKpUmxxixLEBKHhe5qVmZrRL.png)

![应用截图 4](https://www.cmspro.cn/uploads/2026/10/05/55tdVOmu2ZutubVzOHF4Zv2Wja35P52SVMNVZemN.png)

![应用截图 5](https://www.cmspro.cn/uploads/2026/10/05/4rxxs2yPriZvAd3chFoR3z9WRfUGWNhPZg01VlcI.png)

![应用截图 6](https://www.cmspro.cn/uploads/2026/10/05/Z9TO5JOM0z7HGpSyo3N3aRurUfzVtnSPO0GSlGSx.png)

## 3. 目录结构

```
CmsproMddoc/
├── Config/mddoc.php                   # 应用配置（文档存储目录，默认 mddoc）
├── Routes/
│   ├── admin.php                      # 后台页面与文件管理 API 路由
│   ├── home.php                       # 前台文档中心页面路由
│   └── front-api.php                  # 前台公开 API 路由（限流 60 次/分钟）
├── Controllers/
│   ├── Admin/DocManageController.php  # 后台文件管理（页面 + 目录/文件/设置 API）
│   ├── Api/DocApiController.php       # 前台公开 API（结构/内容/下载）
│   └── Home/DocController.php         # 文档中心页面
├── Services/
│   ├── DocService.php                 # 文件系统业务服务（结构扫描/CRUD/安全校验）
│   └── BrandService.php               # 前台品牌与导航配置读写
├── Assets/
│   ├── css/doc.css                    # 前台样式（亮/暗主题变量）
│   ├── js/doc.js                      # 前台交互（导航/搜索/目录/主题）
│   ├── js/marked.min.js               # Markdown 渲染
│   └── lib/highlight.js/              # 代码高亮（多语言 + github-dark 主题）
├── Tests/
│   ├── MddocTestCase.php              # 应用测试基类
│   └── Feature/                       # 后台管理与前台接口功能测试
├── Views/
│   ├── Admin/index.blade.php          # 文件管理页（目录树 + 分屏编辑器）
│   ├── Admin/settings.blade.php       # 应用设置页（品牌/菜单）
│   ├── Home/index.blade.php           # 文档中心三栏页面
│   └── layouts/home.blade.php         # 前台布局
├── Install.php                        # 安装/卸载/升级逻辑
├── ServiceProvider.php                # 配置合并、路由与视图注册
├── icon.svg                           # 应用图标
├── manifest.json                      # 应用元数据唯一声明源
├── README.md                          # 本文件
└── doc/                               # 应用文档
```

## 4. 应用文档

| 文档 | 说明 |
|------|------|
| [CmsPro-Markdown文档应用文档.md](doc/CmsPro-Markdown文档应用文档.md) | 应用概述、功能说明、技术架构、接口与数据结构 |
| [CmsPro-Markdown文档-使用指南.md](doc/CmsPro-Markdown文档-使用指南.md) | 环境要求、安装启用、权限配置与功能验证步骤 |
| [CmsPro-Markdown文档-扩展指南.md](doc/CmsPro-Markdown文档-扩展指南.md) | DocService 调用方式与钩子扩展点说明 |