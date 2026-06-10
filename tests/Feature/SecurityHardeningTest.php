<?php

use App\Enums\UserRole;
use App\Models\Article;
use App\Models\ArticleView;
use App\Models\MediaItem;
use App\Models\Page;

describe('security headers', function () {
    it('sets X-Frame-Options header on responses', function () {
        $this->get('/')->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    });

    it('sets X-Content-Type-Options header on responses', function () {
        $this->get('/')->assertHeader('X-Content-Type-Options', 'nosniff');
    });

    it('sets Referrer-Policy header on responses', function () {
        $this->get('/')->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    });

    it('sets Permissions-Policy header on responses', function () {
        $this->get('/')->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    });

    it('sets a Content-Security-Policy header with frame-ancestors on the frontend', function () {
        $response = $this->get('/');
        $csp = $response->headers->get('Content-Security-Policy');

        expect($csp)->not->toBeNull()
            ->and($csp)->toContain("default-src 'self'")
            ->and($csp)->toContain("frame-ancestors 'self'")
            ->and($csp)->toContain("object-src 'none'");
    });

    it('does not advertise HSTS over plain HTTP', function () {
        $this->get('/')->assertHeaderMissing('Strict-Transport-Security');
    });

    it('advertises HSTS over HTTPS', function () {
        $this->get('https://localhost/')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    });

    it('does not grant unsafe-eval to the public frontend CSP', function () {
        $csp = $this->get('/')->headers->get('Content-Security-Policy');

        expect($csp)->toContain("script-src 'self'")
            ->and($csp)->not->toContain('unsafe-eval');
    });

    it('grants unsafe-eval to the admin panel CSP for Alpine', function () {
        $admin = createUser(UserRole::Admin);

        $csp = $this->actingAs($admin)->get('/admin')->headers->get('Content-Security-Policy');

        expect($csp)->toContain("'unsafe-eval'");
    });
});

describe('view policy (defense in depth)', function () {
    it('lets an editor view their own and orphan records but not another editor\'s', function () {
        $owner = createUser(UserRole::Editor);
        $other = createUser(UserRole::Editor);

        $ownArticle = Article::forceCreate(['title' => 'Mine', 'slug' => 'mine', 'content' => 'C', 'is_published' => false, 'user_id' => $owner->id]);
        $otherArticle = Article::forceCreate(['title' => 'Theirs', 'slug' => 'theirs', 'content' => 'C', 'is_published' => false, 'user_id' => $other->id]);
        $orphan = Article::forceCreate(['title' => 'Orphan', 'slug' => 'orphan', 'content' => 'C', 'is_published' => false, 'user_id' => null]);

        expect($owner->can('view', $ownArticle))->toBeTrue()
            ->and($owner->can('view', $orphan))->toBeTrue()
            ->and($owner->can('view', $otherArticle))->toBeFalse();
    });

    it('lets an admin view any record', function () {
        $admin = createUser(UserRole::Admin);
        $editor = createUser(UserRole::Editor);
        $article = Article::forceCreate(['title' => 'X', 'slug' => 'x', 'content' => 'C', 'is_published' => false, 'user_id' => $editor->id]);

        expect($admin->can('view', $article))->toBeTrue();
    });
});

describe('rate limiting', function () {
    it('applies the throttle middleware to public frontend routes', function () {
        $route = collect(app('router')->getRoutes())
            ->first(fn ($r) => $r->getName() === 'articles.show');

        expect($route)->not->toBeNull()
            ->and($route->gatherMiddleware())->toContain('throttle:60,1');
    });
});

describe('trusted proxy spoofing', function () {
    it('ignores a spoofed X-Forwarded-For when no proxy is trusted', function () {
        $article = Article::create([
            'title' => 'Tracked',
            'slug' => 'tracked',
            'content' => 'C',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->get('/article/tracked', ['X-Forwarded-For' => '1.2.3.4'])->assertOk();

        // request()->ip() falls back to REMOTE_ADDR (127.0.0.1 in tests), not the spoofed header.
        expect(ArticleView::first()->ip_address)->toBe('127.0.0.1');
    });
});

describe('plain-text field sanitization', function () {
    it('strips HTML from article title and excerpt on save', function () {
        $article = Article::create([
            'title' => 'Hello <script>alert(1)</script>World',
            'slug' => 'xss-title',
            'excerpt' => 'Intro <img src=x onerror=alert(1)> text',
            'content' => '<p>Body</p>',
            'is_published' => true,
            'published_at' => now(),
        ]);

        expect($article->fresh()->title)->toBe('Hello alert(1)World')
            ->and($article->fresh()->excerpt)->toBe('Intro  text');
    });

    it('strips HTML from page title and excerpt on save', function () {
        $page = Page::create([
            'title' => 'Title <b>bold</b>',
            'slug' => 'xss-page',
            'excerpt' => 'Lead <script>evil()</script>',
            'content' => '<p>Body</p>',
            'is_published' => true,
        ]);

        expect($page->fresh()->title)->toBe('Title bold')
            ->and($page->fresh()->excerpt)->toBe('Lead evil()');
    });
});

describe('unpublished parent page leak', function () {
    it('does not expose unpublished parent page in breadcrumb', function () {
        $parent = Page::create([
            'title' => 'Draft Parent',
            'slug' => 'draft-parent',
            'content' => 'Secret draft content',
            'is_published' => false,
        ]);

        $child = Page::create([
            'title' => 'Published Child',
            'slug' => 'published-child',
            'content' => '<p>Child content</p>',
            'is_published' => true,
            'parent_id' => $parent->id,
        ]);

        $this->get('/page/published-child')
            ->assertOk()
            ->assertSee('Published Child')
            ->assertDontSee('Draft Parent');
    });

    it('shows published parent page in breadcrumb', function () {
        $parent = Page::create([
            'title' => 'Published Parent',
            'slug' => 'published-parent',
            'content' => 'Parent content',
            'is_published' => true,
        ]);

        $child = Page::create([
            'title' => 'Published Child',
            'slug' => 'pub-child',
            'content' => '<p>Child content</p>',
            'is_published' => true,
            'parent_id' => $parent->id,
        ]);

        $this->get('/page/pub-child')
            ->assertOk()
            ->assertSee('Published Parent');
    });
});

describe('article view mass assignment', function () {
    it('does not allow mass assignment of article_id', function () {
        $view = new ArticleView();
        $view->fill(['article_id' => 999, 'ip_address' => '127.0.0.1', 'viewed_at' => now()]);

        expect($view->article_id)->toBeNull()
            ->and($view->ip_address)->toBe('127.0.0.1');
    });

    it('allows article_id through relationship create', function () {
        $article = Article::create([
            'title' => 'Legit',
            'slug' => 'legit',
            'content' => 'Content',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $view = $article->views()->create([
            'ip_address' => '127.0.0.1',
            'viewed_at' => now(),
        ]);

        expect($view->article_id)->toBe($article->id);
    });

    it('excludes article_id from fillable', function () {
        $view = new ArticleView();

        expect($view->getFillable())
            ->toContain('ip_address', 'user_agent', 'referer', 'viewed_at')
            ->not->toContain('article_id');
    });
});

describe('media item collection', function () {
    it('restricts accepted MIME types on the images collection', function () {
        $item = new MediaItem();
        $item->registerMediaCollections();

        $collection = collect($item->mediaCollections)->firstWhere('name', 'images');

        expect($collection)->not->toBeNull()
            ->and($collection->acceptsMimeTypes)->toBe([
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            ]);
    });
});

describe('cache deserialization protection', function () {
    it('disables serializable classes on database cache store', function () {
        $config = config('cache.stores.database');

        expect($config)->toHaveKey('serializable_classes')
            ->and($config['serializable_classes'])->toBeFalse();
    });
});
