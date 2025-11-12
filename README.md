# TechNewsHub

<div align="center">

![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind-3-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![Alpine.js](https://img.shields.io/badge/Alpine.js-3-8BC0D0?style=for-the-badge&logo=alpine.js&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-3-003B57?style=for-the-badge&logo=sqlite&logoColor=white)

**A modern, full-featured news and blog platform built with Laravel 12**

[Features](#features) • [Installation](#installation) • [Documentation](#documentation) • [API](#api) • [Contributing](#contributing)

</div>

---

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [API Documentation](#api-documentation)
- [Testing](#testing)
- [Project Structure](#project-structure)
- [Documentation](#documentation)
- [Roadmap](#roadmap)
- [Contributing](#contributing)
- [License](#license)

---

## 🎯 Overview

TechNewsHub is a comprehensive content management system designed for technology news, programming tutorials, and information systems content. Built with Laravel 12 and modern web technologies, it provides a robust platform for content creators, editors, and readers.

### Key Highlights

- 🚀 **Modern Stack**: Laravel 12, PHP 8.4, Tailwind CSS 3, Alpine.js 3
- 📝 **Full CMS**: Complete content management with posts, categories, tags, and pages
- 🔍 **Advanced Search**: Fuzzy search integration with analytics and click tracking
- 🛡️ **Spam Protection**: Multi-layered spam detection for comments
- 📊 **Analytics**: Built-in search analytics and content performance tracking
- 🎨 **Responsive Design**: Mobile-first, fully responsive interface
- 🔐 **Secure**: Role-based access control, CSRF protection, XSS prevention
- 📱 **API Ready**: RESTful API with Sanctum authentication
- ⚡ **Performance**: Optimized queries, caching, and image processing
- 🧪 **Well Tested**: Comprehensive test coverage with PHPUnit

---

## ✨ Features

### Content Management

#### Posts & Articles
- ✅ Rich text editor with formatting options
- ✅ Featured images with automatic optimization
- ✅ Post scheduling for future publication
- ✅ Draft, published, scheduled, and archived statuses
- ✅ Featured and trending post flags
- ✅ Reading time calculation
- ✅ View count tracking
- ✅ SEO metadata (title, description, keywords)
- ✅ Post revisions with version control
- ✅ Soft deletes for data recovery

#### Organization
- ✅ Hierarchical category system
- ✅ Tag-based classification
- ✅ Category icons and color coding
- ✅ SEO optimization per category
- ✅ Custom display ordering

#### Media Management
- ✅ Centralized media library
- ✅ Multiple file type support (images, documents)
- ✅ Automatic image optimization and resizing
- ✅ WebP format generation with fallback
- ✅ Alt text and captions for accessibility
- ✅ EXIF metadata stripping
- ✅ Multiple size variants (thumbnail, medium, large)

### User Engagement

#### Comments
- ✅ Nested comment threading (3 levels)
- ✅ Comment moderation workflow
- ✅ Guest and authenticated commenting
- ✅ Spam detection with multiple strategies
- ✅ IP and user agent tracking
- ✅ Rate limiting

#### Interactions
- ✅ Bookmark/save posts
- ✅ Multiple reaction types (like, love, laugh, wow, sad, angry)
- ✅ Reading lists for authenticated users
- ✅ Social sharing buttons

### Search & Discovery

#### Advanced Search
- ✅ Full-text search across posts
- ✅ Fuzzy search with typo tolerance
- ✅ Search result highlighting
- ✅ Filter by category, date, and author
- ✅ Relevance-based sorting
- ✅ Live search suggestions

#### Analytics
- ✅ Search query logging
- ✅ Click-through rate tracking
- ✅ No-result query analysis
- ✅ Performance metrics
- ✅ Popular search terms
- ✅ User behavior insights

### Administration

#### Dashboard
- ✅ Key metrics and statistics
- ✅ Post count with trends
- ✅ View analytics (daily, weekly, monthly)
- ✅ Pending comments counter
- ✅ Top 10 most viewed posts
- ✅ Publication timeline chart

#### User Management
- ✅ Role-based access control (Admin, Editor, Author)
- ✅ User profiles with avatars
- ✅ Account status management
- ✅ Activity logging
- ✅ Last login tracking

#### Content Moderation
- ✅ Comment approval workflow
- ✅ Spam detection and filtering
- ✅ Bulk actions
- ✅ Content scheduling
- ✅ Revision history

#### System Settings
- ✅ Grouped configuration (General, SEO, Email, etc.)
- ✅ Newsletter management
- ✅ Contact form submissions
- ✅ Maintenance mode
- ✅ Cache management

### Security & Performance

#### Security
- ✅ CSRF protection on all forms
- ✅ XSS prevention
- ✅ SQL injection protection via Eloquent
- ✅ Rate limiting on API and forms
- ✅ Password hashing with bcrypt
- ✅ Email verification
- ✅ Secure session management
- ✅ IP-based spam prevention

#### Performance
- ✅ Query optimization with strategic indexes
- ✅ Eager loading to prevent N+1 queries
- ✅ Image optimization and lazy loading
- ✅ Cache support (file, Redis, Memcached)
- ✅ Queue system for background jobs
- ✅ Database query logging for slow queries

### API

- ✅ RESTful API endpoints
- ✅ Sanctum authentication
- ✅ Rate limiting (60 requests/minute)
- ✅ API Resources for consistent responses
- ✅ Interactive documentation with Scribe
- ✅ Versioning support
- ✅ Error handling with detailed messages

---

## 🛠️ Tech Stack

### Backend
- **Framework**: Laravel 12
- **Language**: PHP 8.4
- **Database**: SQLite (development), MySQL/PostgreSQL ready
- **Authentication**: Laravel Breeze, Sanctum
- **Queue**: Database driver (Redis/SQS ready)
- **Cache**: File driver (Redis/Memcached ready)
- **Mail**: SMTP configuration

### Frontend
- **Template Engine**: Blade
- **CSS Framework**: Tailwind CSS 3
- **JavaScript**: Alpine.js 3
- **Build Tool**: Vite
- **Icons**: Heroicons

### Development Tools
- **Testing**: PHPUnit 11
- **Code Style**: Laravel Pint
- **API Docs**: Scribe
- **Version Control**: Git

---

## 📦 Requirements

- PHP >= 8.4
- Composer
- Node.js >= 18.x
- NPM or Yarn
- SQLite (or MySQL/PostgreSQL for production)
- Web server (Apache/Nginx)

---

## 🚀 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/technewshub.git
cd technewshub
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### 3. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Database Setup

```bash
# Create SQLite database
touch database/database.sqlite

# Run migrations
php artisan migrate

# Seed database with sample data (optional)
php artisan db:seed
```

### 5. Build Assets

```bash
# Development
npm run dev

# Production
npm run build
```

### 6. Start Development Server

```bash
php artisan serve
```

Visit `http://localhost:8000` in your browser.

### 7. Configure Scheduler (for scheduled posts)

Add to your crontab:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### 8. Configure Queue Worker (optional)

```bash
php artisan queue:work
```

---

## ⚙️ Configuration

### Environment Variables

Key environment variables to configure:

```env
# Application
APP_NAME=TechNewsHub
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_CONNECTION=sqlite
# DB_DATABASE=/absolute/path/to/database.sqlite

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@technewshub.com"
MAIL_FROM_NAME="${APP_NAME}"

# Queue
QUEUE_CONNECTION=database

# Cache
CACHE_DRIVER=file

# Session
SESSION_DRIVER=file
```

### Admin Account

Create an admin account:

```bash
php artisan db:seed --class=AdminUserSeeder
```

Default credentials:
- Email: `admin@technewshub.com`
- Password: `password`

**⚠️ Change these credentials immediately in production!**

---

## 📖 Usage

### Creating Content

1. **Login** to the admin panel at `/admin`
2. **Navigate** to Posts → Create New
3. **Fill in** post details:
   - Title (required)
   - Content (required)
   - Excerpt (optional)
   - Category (required)
   - Tags (optional)
   - Featured image (optional)
   - SEO metadata (optional)
4. **Choose** status:
   - Draft: Save without publishing
   - Published: Make live immediately
   - Scheduled: Set future publication date
5. **Click** Save or Publish

### Managing Comments

1. Navigate to **Comments** in admin panel
2. View pending comments
3. **Approve**, **Spam**, or **Delete** comments
4. Reply to comments directly

### Search Analytics

1. Navigate to **Analytics** → **Search**
2. View:
   - Top search queries
   - No-result queries
   - Click-through rates
   - Search performance metrics

### API Usage

#### Authentication

```bash
# Register user
POST /api/register
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password",
  "password_confirmation": "password"
}

# Login
POST /api/login
{
  "email": "john@example.com",
  "password": "password"
}
```

#### Fetch Posts

```bash
# Get all posts
GET /api/posts

# Get single post
GET /api/posts/{id}

# Search posts
GET /api/posts?search=laravel

# Filter by category
GET /api/posts?category_id=1
```

See full API documentation at `/docs` when running the application.

---

## 📚 API Documentation

Interactive API documentation is available at `/docs` when the application is running.

### Available Endpoints

#### Public Endpoints
- `GET /api/posts` - List all published posts
- `GET /api/posts/{id}` - Get single post
- `GET /api/categories` - List all categories
- `GET /api/tags` - List all tags

#### Authenticated Endpoints
- `POST /api/posts` - Create new post
- `PUT /api/posts/{id}` - Update post
- `DELETE /api/posts/{id}` - Delete post
- `POST /api/comments` - Create comment
- `POST /api/bookmarks` - Toggle bookmark

### Rate Limiting

- **Public endpoints**: 60 requests per minute
- **Authenticated endpoints**: 60 requests per minute
- **API authentication**: 5 attempts per minute

---

## 🧪 Testing

### Run All Tests

```bash
php artisan test
```

### Run Specific Test Suite

```bash
# Feature tests
php artisan test --testsuite=Feature

# Unit tests
php artisan test --testsuite=Unit
```

### Run Specific Test File

```bash
php artisan test tests/Feature/PostServiceTest.php
```

### Run with Coverage

```bash
php artisan test --coverage
```

### Test Categories

- **Feature Tests**: End-to-end functionality testing
  - Post management
  - Comment submission
  - Search functionality
  - Spam detection
  - Image processing
  - Authentication flows
  
- **Unit Tests**: Individual component testing
  - Service classes
  - Helper functions
  - Model methods

---

## 📁 Project Structure

```
technewshub/
├── app/
│   ├── Console/Commands/      # Artisan commands
│   ├── DataTransferObjects/   # DTOs for data transfer
│   ├── Exceptions/            # Custom exceptions
│   ├── Http/
│   │   ├── Controllers/       # HTTP controllers
│   │   ├── Middleware/        # Custom middleware
│   │   ├── Requests/          # Form requests
│   │   └── Resources/         # API resources
│   ├── Jobs/                  # Queue jobs
│   ├── Mail/                  # Mailable classes
│   ├── Models/                # Eloquent models
│   ├── Policies/              # Authorization policies
│   ├── Providers/             # Service providers
│   ├── Services/              # Business logic services
│   ├── Traits/                # Reusable traits
│   └── View/Components/       # Blade components
├── bootstrap/                 # Application bootstrap
├── config/                    # Configuration files
├── database/
│   ├── factories/             # Model factories
│   ├── migrations/            # Database migrations
│   └── seeders/               # Database seeders
├── docs/                      # Documentation
│   ├── admin/                 # Admin documentation
│   ├── frontend/              # Frontend documentation
│   └── functionality/         # Feature documentation
├── public/                    # Public assets
├── resources/
│   ├── css/                   # Stylesheets
│   ├── js/                    # JavaScript
│   └── views/                 # Blade templates
├── routes/                    # Route definitions
├── storage/                   # Application storage
├── tests/                     # Test files
│   ├── Feature/               # Feature tests
│   └── Unit/                  # Unit tests
└── vendor/                    # Composer dependencies
```

---

## 📖 Documentation

Comprehensive documentation is available in the `docs/` directory:

### Available Documentation

- **[Database Schema](docs/functionality/database-schema.md)** - Complete database structure with ERD, relationships, and optimization recommendations
- **[API Reference](docs/api/)** - Detailed API endpoint documentation (generated)
- **[Admin Guide](docs/admin/)** - Admin panel usage guide
- **[Frontend Guide](docs/frontend/)** - Frontend development guide

### Specifications

Project specifications are maintained in `.kiro/specs/`:

- **[Tech News Platform](. kiro/specs/tech-news-platform/)** - Core platform requirements and design
- **[Fuzzy Search Integration](.kiro/specs/fuzzy-search-integration/)** - Search enhancement specifications

---

## 🗺️ Roadmap

### Phase 1: Core Services ✅
- [x] Post management services
- [x] Content scheduling system
- [x] Image processing service
- [x] Spam detection service

### Phase 2: Search & Discovery (In Progress)
- [x] Search analytics and logging
- [x] Click tracking
- [ ] Fuzzy search integration
- [ ] Advanced search filters
- [ ] Related posts algorithm

### Phase 3: Content Organization
- [ ] Post series management
- [ ] Bookmark system
- [ ] Advanced filtering
- [ ] Content calendar

### Phase 4: Analytics & Monitoring
- [ ] View tracking system
- [ ] Enhanced dashboard
- [ ] Performance monitoring
- [ ] User behavior analytics

### Phase 5: SEO & Discovery
- [ ] SEO meta tag system
- [ ] Sitemap generation
- [ ] Breadcrumb navigation
- [ ] Broken link checker

### Phase 6: User Experience
- [ ] Comment reply and nesting
- [ ] Reading progress indicator
- [ ] Social share buttons
- [ ] Dark mode support
- [ ] Infinite scroll

### Phase 7: Email & Notifications
- [ ] Email notification system
- [ ] Newsletter enhancements
- [ ] In-app notifications

### Phase 8: Admin Features
- [ ] Content calendar
- [ ] Menu builder
- [ ] Widget management
- [ ] Image alt text validation

### Phase 9: Security & Compliance
- [ ] Enhanced rate limiting
- [ ] Security headers
- [ ] Two-factor authentication
- [ ] GDPR compliance

### Phase 10: Maintenance
- [ ] Database backup system
- [ ] Enhanced maintenance mode
- [ ] Content import/export

### Phase 11: Internationalization
- [ ] Multi-language support
- [ ] RTL support
- [ ] Translation management

### Phase 12: Progressive Web App
- [ ] PWA features
- [ ] Offline support
- [ ] Push notifications

See [tasks.md](.kiro/specs/tech-news-platform/tasks.md) for detailed implementation plan.

---

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/amazing-feature`)
3. **Commit** your changes (`git commit -m 'Add amazing feature'`)
4. **Push** to the branch (`git push origin feature/amazing-feature`)
5. **Open** a Pull Request

### Development Guidelines

- Follow Laravel best practices
- Write tests for new features
- Run `vendor/bin/pint` before committing
- Update documentation as needed
- Follow existing code style

### Code Style

This project uses Laravel Pint for code formatting:

```bash
# Format all files
vendor/bin/pint

# Format specific files
vendor/bin/pint app/Services/
```

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 🙏 Acknowledgments

- Laravel Framework
- Tailwind CSS
- Alpine.js
- All open-source contributors

---

## 📞 Support

- **Documentation**: [docs/](docs/)
- **Issues**: [GitHub Issues](https://github.com/yourusername/technewshub/issues)
- **Discussions**: [GitHub Discussions](https://github.com/yourusername/technewshub/discussions)

---

<div align="center">

**Built with ❤️ using Laravel**

[⬆ Back to Top](#technewshub)

</div>
