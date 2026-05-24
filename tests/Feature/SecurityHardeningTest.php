<?php

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
