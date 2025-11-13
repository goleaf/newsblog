<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            {{ __('Widget Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    
                    <div x-data="widgetManager()" x-init="init()">
                        <!-- Add Widget Button -->
                        <div class="mb-6">
                            <button @click="showAddModal = true" 
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                                Add Widget
                            </button>
                        </div>

                        <!-- Widget Areas -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach($widgetAreas as $area)
                                <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-4">
                                    <h3 class="text-lg font-semibold mb-4">{{ $area->name }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ $area->description }}</p>
                                    
                                    <div class="space-y-3" 
                                         data-area-id="{{ $area->id }}"
                                         x-ref="area_{{ $area->id }}">
                                        @foreach($area->widgets as $widget)
                                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg cursor-move"
                                                 data-widget-id="{{ $widget->id }}"
                                                 draggable="true"
                                                 @dragstart="dragStart($event)"
                                                 @dragend="dragEnd($event)"
                                                 @dragover.prevent
                                                 @drop="drop($event)">
                                                <div class="flex justify-between items-start">
                                                    <div class="flex-1">
                                                        <h4 class="font-medium">{{ $widget->title }}</h4>
                                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                                            Type: {{ $widget->type }}
                                                        </p>
                                                    </div>
                                                    <div class="flex gap-2">
                                                        <button @click="toggleWidget({{ $widget->id }})"
                                                                :class="widgets[{{ $widget->id }}]?.active ? 'text-green-600' : 'text-gray-400'"
                                                                class="hover:text-green-700">
                                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                            </svg>
                                                        </button>
                                                        <button @click="editWidget({{ $widget->id }})"
                                                                class="text-blue-600 hover:text-blue-700">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                            </svg>
                                                        </button>
                                                        <button @click="deleteWidget({{ $widget->id }})"
                                                                class="text-red-600 hover:text-red-700">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                        
                                        @if($area->widgets->isEmpty())
                                            <p class="text-gray-500 dark:text-gray-400 text-sm text-center py-4">
                                                No widgets in this area. Drag widgets here.
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Add Widget Modal -->
                        <div x-show="showAddModal" 
                             x-cloak
                             class="fixed inset-0 z-50 overflow-y-auto"
                             @click.self="showAddModal = false">
                            <div class="flex items-center justify-center min-h-screen px-4">
                                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                                
                                <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-lg w-full p-6">
                                    <h3 class="text-lg font-semibold mb-4">Add Widget</h3>
                                    
                                    <form @submit.prevent="addWidget()">
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium mb-2">Widget Area</label>
                                                <select x-model="newWidget.widget_area_id" required
                                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                                                    <option value="">Select area...</option>
                                                    @foreach($widgetAreas as $area)
                                                        <option value="{{ $area->id }}">{{ $area->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium mb-2">Widget Type</label>
                                                <select x-model="newWidget.type" @change="updateWidgetSettings()" required
                                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                                                    <option value="">Select type...</option>
                                                    @foreach($availableTypes as $key => $type)
                                                        <option value="{{ $key }}">{{ $type['name'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium mb-2">Title</label>
                                                <input type="text" x-model="newWidget.title" required
                                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                                            </div>
                                            
                                            <!-- Dynamic Settings -->
                                            <template x-if="newWidget.type && availableTypes[newWidget.type]">
                                                <div class="space-y-3">
                                                    <template x-for="(setting, key) in availableTypes[newWidget.type].settings" :key="key">
                                                        <div>
                                                            <label class="block text-sm font-medium mb-2" x-text="setting.label"></label>
                                                            <template x-if="setting.type === 'number'">
                                                                <input type="number" 
                                                                       x-model="newWidget.settings[key]"
                                                                       :placeholder="setting.default"
                                                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                                                            </template>
                                                            <template x-if="setting.type === 'checkbox'">
                                                                <input type="checkbox" 
                                                                       x-model="newWidget.settings[key]"
                                                                       class="rounded border-gray-300 dark:border-gray-600">
                                                            </template>
                                                            <template x-if="setting.type === 'textarea'">
                                                                <textarea x-model="newWidget.settings[key]"
                                                                          rows="4"
                                                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700"></textarea>
                                                            </template>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                        
                                        <div class="flex justify-end gap-3 mt-6">
                                            <button type="button" @click="showAddModal = false"
                                                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                                                Cancel
                                            </button>
                                            <button type="submit"
                                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                                                Add Widget
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function widgetManager() {
            return {
                showAddModal: false,
                widgets: @json($widgetAreas->flatMap->widgets->keyBy('id')),
                availableTypes: @json($availableTypes),
                newWidget: {
                    widget_area_id: '',
                    type: '',
                    title: '',
                    settings: {},
                    active: true
                },
                draggedElement: null,

                init() {
                    // Initialize
                },

                updateWidgetSettings() {
                    if (this.newWidget.type && this.availableTypes[this.newWidget.type]) {
                        const settings = this.availableTypes[this.newWidget.type].settings;
                        this.newWidget.settings = {};
                        Object.keys(settings).forEach(key => {
                            this.newWidget.settings[key] = settings[key].default;
                        });
                    }
                },

                async addWidget() {
                    try {
                        const response = await fetch('{{ route("admin.widgets.store") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(this.newWidget)
                        });

                        const data = await response.json();
                        
                        if (data.success) {
                            window.location.reload();
                        }
                    } catch (error) {
                        console.error('Error adding widget:', error);
                        alert('Failed to add widget');
                    }
                },

                async toggleWidget(widgetId) {
                    try {
                        const response = await fetch(`/admin/widgets/${widgetId}/toggle`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });

                        const data = await response.json();
                        
                        if (data.success) {
                            this.widgets[widgetId].active = data.active;
                        }
                    } catch (error) {
                        console.error('Error toggling widget:', error);
                    }
                },

                async deleteWidget(widgetId) {
                    if (!confirm('Are you sure you want to delete this widget?')) {
                        return;
                    }

                    try {
                        const response = await fetch(`/admin/widgets/${widgetId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });

                        const data = await response.json();
                        
                        if (data.success) {
                            window.location.reload();
                        }
                    } catch (error) {
                        console.error('Error deleting widget:', error);
                    }
                },

                dragStart(event) {
                    this.draggedElement = event.target;
                    event.target.classList.add('opacity-50');
                },

                dragEnd(event) {
                    event.target.classList.remove('opacity-50');
                },

                async drop(event) {
                    event.preventDefault();
                    
                    if (!this.draggedElement) return;

                    const dropTarget = event.target.closest('[data-widget-id]') || event.target.closest('[data-area-id]');
                    if (!dropTarget) return;

                    const draggedId = this.draggedElement.dataset.widgetId;
                    const targetArea = dropTarget.closest('[data-area-id]');
                    
                    if (!targetArea) return;

                    // Reorder widgets
                    const widgets = Array.from(targetArea.querySelectorAll('[data-widget-id]'));
                    const draggedIndex = widgets.indexOf(this.draggedElement);
                    const targetIndex = widgets.indexOf(dropTarget);

                    if (draggedIndex !== targetIndex) {
                        if (draggedIndex < targetIndex) {
                            dropTarget.after(this.draggedElement);
                        } else {
                            dropTarget.before(this.draggedElement);
                        }

                        await this.saveOrder();
                    }
                },

                async saveOrder() {
                    const widgetsData = [];
                    
                    document.querySelectorAll('[data-area-id]').forEach(area => {
                        const areaId = area.dataset.areaId;
                        const widgets = area.querySelectorAll('[data-widget-id]');
                        
                        widgets.forEach((widget, index) => {
                            widgetsData.push({
                                id: parseInt(widget.dataset.widgetId),
                                order: index,
                                widget_area_id: parseInt(areaId)
                            });
                        });
                    });

                    try {
                        const response = await fetch('{{ route("admin.widgets.reorder") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ widgets: widgetsData })
                        });

                        const data = await response.json();
                        
                        if (!data.success) {
                            alert('Failed to save order');
                        }
                    } catch (error) {
                        console.error('Error saving order:', error);
                    }
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
