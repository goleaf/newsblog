<?php

namespace Tests\Feature\Frontend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaseComponentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_base_layout_renders_correctly(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Skip to main content', false);
    }

    public function test_skeleton_loader_card_renders(): void
    {
        $view = $this->blade('<x-ui.skeleton-loader type="card" :count="2" />');

        $view->assertSee('animate-pulse', false);
    }

    public function test_skeleton_loader_list_renders(): void
    {
        $view = $this->blade('<x-ui.skeleton-loader type="list" :count="3" />');

        $view->assertSee('animate-pulse', false);
    }

    public function test_loading_spinner_renders_with_default_size(): void
    {
        $view = $this->blade('<x-ui.loading-spinner />');

        $view->assertSee('animate-spin', false);
        $view->assertSee('Loading...', false);
    }

    public function test_loading_spinner_renders_with_text(): void
    {
        $view = $this->blade('<x-ui.loading-spinner text="Please wait..." />');

        $view->assertSee('Please wait...');
    }

    public function test_error_message_renders_error_type(): void
    {
        $view = $this->blade('<x-ui.error-message 
            type="error" 
            title="Error Title" 
            message="Error message content" 
        />');

        $view->assertSee('Error Title');
        $view->assertSee('Error message content');
    }

    public function test_error_message_renders_warning_type(): void
    {
        $view = $this->blade('<x-ui.error-message 
            type="warning" 
            title="Warning Title" 
            message="Warning message content" 
        />');

        $view->assertSee('Warning Title');
        $view->assertSee('Warning message content');
    }

    public function test_error_message_with_retry_action(): void
    {
        $view = $this->blade('<x-ui.error-message 
            type="error" 
            message="Failed to load" 
            :retry-action="\'loadData()\'"
            retry-text="Retry"
        />');

        $view->assertSee('Failed to load');
        $view->assertSee('Retry');
        $view->assertSee('loadData()', false);
    }

    public function test_empty_state_renders_with_default_content(): void
    {
        $view = $this->blade('<x-ui.empty-state />');

        $view->assertSee('No results found');
    }

    public function test_empty_state_renders_with_custom_content(): void
    {
        $view = $this->blade('<x-ui.empty-state 
            title="No articles found" 
            message="Try adjusting your search criteria"
            action-text="Clear Filters"
            action-url="/search"
        />');

        $view->assertSee('No articles found');
        $view->assertSee('Try adjusting your search criteria');
        $view->assertSee('Clear Filters');
    }

    public function test_badge_renders_with_default_variant(): void
    {
        $view = $this->blade('<x-ui.badge>Default Badge</x-ui.badge>');

        $view->assertSee('Default Badge');
    }

    public function test_badge_renders_with_primary_variant(): void
    {
        $view = $this->blade('<x-ui.badge variant="primary">Primary Badge</x-ui.badge>');

        $view->assertSee('Primary Badge');
        $view->assertSee('bg-primary-100', false);
    }

    public function test_badge_renders_with_success_variant(): void
    {
        $view = $this->blade('<x-ui.badge variant="success">Success Badge</x-ui.badge>');

        $view->assertSee('Success Badge');
        $view->assertSee('bg-green-100', false);
    }

    public function test_badge_renders_removable(): void
    {
        $view = $this->blade('<x-ui.badge :removable="true">Removable Badge</x-ui.badge>');

        $view->assertSee('Removable Badge');
        $view->assertSee('Remove', false);
    }

    public function test_modal_renders_with_title(): void
    {
        $view = $this->blade('<x-ui.modal id="test-modal" title="Test Modal">
            Modal Content
        </x-ui.modal>');

        $view->assertSee('Test Modal');
        $view->assertSee('Modal Content');
        $view->assertSee('test-modal', false);
    }

    public function test_modal_renders_with_close_button(): void
    {
        $view = $this->blade('<x-ui.modal id="test-modal" :closeable="true">
            Modal Content
        </x-ui.modal>');

        $view->assertSee('Close modal', false);
    }

    public function test_toast_notification_component_renders(): void
    {
        $view = $this->blade('<x-ui.toast-notification />');

        $view->assertSee('aria-live="polite"', false);
    }
}
