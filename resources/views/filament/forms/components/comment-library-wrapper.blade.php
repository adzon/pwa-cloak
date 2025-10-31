@php
    use App\Models\Comment;
    use Illuminate\Support\Facades\Auth;
    
    // 获取已选中的评论IDs
    $selectedIds = [];
    try {
        // 尝试从多个来源获取数据
        if (isset($getRecord) && is_callable($getRecord)) {
            $record = $getRecord();
            if ($record && $record->exists) {
                // 编辑模式：从数据库加载
                $localeApp = $record->localeApplications()->where('language_id', $languageId)->first();
                if ($localeApp) {
                    $selectedIds = $localeApp->comments()->pluck('comments.id')->toArray();
                }
            }
        }
        
        // 如果上面没有获取到，尝试从表单状态获取
        if (empty($selectedIds) && isset($this) && method_exists($this, 'data')) {
            $data = $this->data;
            if (isset($data['localeApplications'][$languageId]['comment_ids'])) {
                $selectedIds = $data['localeApplications'][$languageId]['comment_ids'];
            }
        }
        
        // 确保是数组
        if (!is_array($selectedIds)) {
            $selectedIds = [];
        }
        
        // 转换为整数数组
        $selectedIds = array_map('intval', array_filter($selectedIds));
        
    } catch (\Exception $e) {
        $selectedIds = [];
    }
    
    // 获取当前用户的所有评论
    $allComments = Comment::where('user_id', Auth::id())
        ->with('language')
        ->orderBy('created_at', 'desc')
        ->get();
    
    $languages = \App\Models\Language::orderBy('id')->get();
@endphp

<div x-data="{
    selectedComments: $wire.entangle('data.localeApplications.{{ $languageId }}.comment_ids').live,
    showModal: false,
    editingCommentId: null,
    formData: {
        nickname: '',
        content: '',
        language_id: {{ $languageId }}
    },
    errors: {},
    isDragging: false,
    modalPosition: { x: 0, y: 0 },
    dragStart: { x: 0, y: 0 },
    
    toggleComment(commentId) {
        const index = this.selectedComments.indexOf(commentId);
        if (index > -1) {
            this.selectedComments.splice(index, 1);
        } else {
            this.selectedComments.push(commentId);
        }
    },
    
    isSelected(commentId) {
        return this.selectedComments.includes(commentId);
    },
    
    openAddModal() {
        this.editingCommentId = null;
        this.formData = {
            nickname: '',
            content: '',
            language_id: {{ $languageId }}
        };
        this.errors = {};
        this.modalPosition = { x: 0, y: 0 };
        this.showModal = true;
        document.body.style.overflow = 'hidden';
    },
    
    openEditModal(comment) {
        this.editingCommentId = comment.id;
        this.formData = {
            nickname: comment.nickname,
            content: comment.content,
            language_id: comment.language_id
        };
        this.errors = {};
        this.modalPosition = { x: 0, y: 0 };
        this.showModal = true;
        document.body.style.overflow = 'hidden';
    },
    
    closeModal() {
        this.showModal = false;
        document.body.style.overflow = '';
    },
    
    startDrag(event) {
        this.isDragging = true;
        this.dragStart = {
            x: event.clientX - this.modalPosition.x,
            y: event.clientY - this.modalPosition.y
        };
        event.preventDefault();
    },
    
    onDrag(event) {
        if (this.isDragging) {
            this.modalPosition = {
                x: event.clientX - this.dragStart.x,
                y: event.clientY - this.dragStart.y
            };
        }
    },
    
    stopDrag() {
        this.isDragging = false;
    },
    
    async saveComment() {
        this.errors = {};
        
        if (!this.formData.nickname || this.formData.nickname.length > 20) {
            this.errors.nickname = '昵称必填且不能超过20个字符';
            return;
        }
        if (!this.formData.content || this.formData.content.length < 5 || this.formData.content.length > 500) {
            this.errors.content = '评论内容必填，且在5-500个字符之间';
            return;
        }
        if (!this.formData.language_id) {
            this.errors.language_id = '请选择语言';
            return;
        }
        
        try {
            if (this.editingCommentId) {
                const response = await $wire.call('mountedTableActionData', {
                    action: 'edit',
                    record: this.editingCommentId,
                    data: this.formData
                });
            } else {
                const response = await $wire.call('createComment', this.formData);
            }
            this.closeModal();
            window.location.reload();
        } catch (error) {
            console.error('保存失败:', error);
        }
    },
    
    async deleteComment(commentId) {
        if (confirm('确定要删除这条评论吗？')) {
            try {
                await $wire.call('deleteComment', commentId);
                // 如果删除的评论在选中列表中，也要移除
                const index = this.selectedComments.indexOf(commentId);
                if (index > -1) {
                    this.selectedComments.splice(index, 1);
                }
                window.location.reload();
            } catch (error) {
                console.error('删除失败:', error);
            }
        }
    }
}"
@mousemove.window="onDrag($event)"
@mouseup.window="stopDrag()">

    <div class="fi-fo-field-wrp">
        <div class="flex items-center justify-between gap-2 mb-2">
            <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
                <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                    <span class="text-danger-600 dark:text-danger-400">*</span>
                    APP评论库
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    (已选 <span x-text="selectedComments.length" class="font-medium text-primary-600 dark:text-primary-400"></span> 条)
                </span>
            </label>
            <button
                type="button"
                @click="openAddModal"
                class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-primary fi-btn-color-primary fi-size-sm fi-btn-size-sm gap-1 px-2.5 py-1.5 text-xs shadow-sm bg-primary-600 text-white hover:bg-primary-500 focus-visible:ring-primary-500/50 dark:bg-primary-500 dark:hover:bg-primary-400 dark:focus-visible:ring-primary-400/50 inline-grid"
            >
                <svg class="fi-btn-icon h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                </svg>
                <span>添加</span>
            </button>
        </div>

        <div class="fi-ta-ctn divide-y divide-gray-200 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:divide-white/10 dark:bg-gray-900 dark:ring-white/10">
            <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                <thead class="divide-y divide-gray-200 dark:divide-white/5">
                    <tr class="bg-gray-50 dark:bg-white/5">
                        <th class="fi-ta-header-cell px-3 py-3 sm:first-of-type:ps-6 sm:last-of-type:pe-6" style="width: 1%;">
                            <span class="sr-only">选择</span>
                        </th>
                        <th class="fi-ta-header-cell px-3 py-3 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                            <span class="group flex items-center gap-x-1 whitespace-nowrap justify-start">
                                <span class="fi-ta-header-cell-label text-xs font-semibold text-gray-950 dark:text-white">
                                    昵称
                                </span>
                            </span>
                        </th>
                        <th class="fi-ta-header-cell px-3 py-3 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                            <span class="group flex items-center gap-x-1 whitespace-nowrap justify-start">
                                <span class="fi-ta-header-cell-label text-xs font-semibold text-gray-950 dark:text-white">
                                    评论内容
                                </span>
                            </span>
                        </th>
                        <th class="fi-ta-header-cell px-3 py-3 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                            <span class="group flex items-center gap-x-1 whitespace-nowrap justify-start">
                                <span class="fi-ta-header-cell-label text-xs font-semibold text-gray-950 dark:text-white">
                                    语言
                                </span>
                            </span>
                        </th>
                        <th class="fi-ta-header-cell px-3 py-3 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                            <span class="group flex items-center gap-x-1 whitespace-nowrap justify-start">
                                <span class="fi-ta-header-cell-label text-xs font-semibold text-gray-950 dark:text-white">
                                    操作
                                </span>
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                    @forelse($allComments as $comment)
                        <tr class="fi-ta-row [@media(hover:hover)]:transition [@media(hover:hover)]:duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                                <div class="fi-ta-col-wrp">
                                    <div class="flex w-full items-center gap-x-3 px-3 py-4">
                                        <input
                                            type="checkbox"
                                            :checked="isSelected({{ $comment->id }})"
                                            @change="toggleComment({{ $comment->id }})"
                                            class="fi-checkbox-input rounded border-gray-300 text-primary-600 shadow-sm outline-none transition duration-75 focus:ring-2 focus:ring-primary-600/50 disabled:pointer-events-none disabled:bg-gray-50 disabled:text-gray-50 disabled:checked:bg-gray-400 dark:border-white/10 dark:bg-white/5 dark:focus:ring-primary-500/50 dark:disabled:bg-transparent dark:disabled:checked:bg-gray-600 h-4 w-4"
                                        >
                                    </div>
                                </div>
                            </td>
                            <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                                <div class="fi-ta-col-wrp">
                                    <div class="flex w-full items-center gap-x-3 px-3 py-4">
                                        <div class="fi-ta-text-item inline-flex items-center gap-1.5">
                                            <span class="fi-ta-text-item-label text-sm leading-6 text-gray-950 dark:text-white">
                                                {{ $comment->nickname }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                                <div class="fi-ta-col-wrp">
                                    <div class="flex w-full items-center gap-x-3 px-3 py-4">
                                        <div class="fi-ta-text-item inline-flex items-center gap-1.5 max-w-md">
                                            <span class="fi-ta-text-item-label text-sm leading-6 text-gray-950 dark:text-white truncate">
                                                {{ $comment->content }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                                <div class="fi-ta-col-wrp">
                                    <div class="flex w-full items-center gap-x-3 px-3 py-4">
                                        <div class="fi-ta-text-item inline-flex items-center gap-1.5">
                                            <span class="fi-ta-text-item-label text-sm leading-6 text-gray-950 dark:text-white">
                                                {{ $comment->language->name ?? '-' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                                <div class="fi-ta-col-wrp">
                                    <div class="flex w-full items-center gap-x-3 px-3 py-4">
                                        <div class="fi-ta-actions flex shrink-0 items-center gap-3">
                                            <button
                                                type="button"
                                                @click="openEditModal({{ json_encode([
                                                    'id' => $comment->id,
                                                    'nickname' => $comment->nickname,
                                                    'content' => $comment->content,
                                                    'language_id' => $comment->language_id
                                                ]) }})"
                                                class="fi-link group/link relative inline-flex items-center justify-center outline-none text-sm hover:underline focus-visible:underline fi-size-md gap-1.5 fi-link-size-md fi-color-primary text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                                            >
                                                编辑
                                            </button>
                                            <button
                                                type="button"
                                                @click="deleteComment({{ $comment->id }})"
                                                class="fi-link group/link relative inline-flex items-center justify-center outline-none text-sm hover:underline focus-visible:underline fi-size-md gap-1.5 fi-link-size-md fi-color-danger text-danger-600 hover:text-danger-500 dark:text-danger-400 dark:hover:text-danger-300"
                                            >
                                                删除
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="fi-ta-empty-state px-6 py-12">
                                <div class="fi-ta-empty-state-content mx-auto grid max-w-lg justify-items-center text-center">
                                    <div class="fi-ta-empty-state-icon-ctn mb-4 rounded-full bg-gray-100 p-3 dark:bg-gray-500/20">
                                        <svg class="fi-ta-empty-state-icon h-6 w-6 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                                        </svg>
                                    </div>
                                    <p class="fi-ta-empty-state-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                                        暂无评论
                                    </p>
                                    <p class="fi-ta-empty-state-description text-sm text-gray-500 dark:text-gray-400">
                                        点击"添加"按钮创建新评论
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 添加/编辑评论模态框 -->
    <div
        x-show="showModal"
        x-cloak
        class="fi-modal fixed inset-0 z-50 min-h-full overflow-hidden transition"
        style="display: none;"
    >
        <div class="fi-modal-window pointer-events-none relative flex min-h-full items-center justify-center p-4">
            <!-- 背景遮罩 -->
            <div
                x-show="showModal"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="closeModal()"
                class="fi-modal-close-overlay absolute inset-0 bg-gray-950/50 dark:bg-gray-950/75"
            ></div>

            <!-- 模态框内容 -->
            <div
                x-show="showModal"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                :style="`transform: translate(${modalPosition.x}px, ${modalPosition.y}px)`"
                class="fi-modal-content pointer-events-auto relative w-full max-w-lg rounded-xl bg-white shadow-xl ring-1 ring-gray-950/5 transition dark:bg-gray-900 dark:ring-white/10"
                :class="{ 'cursor-move': isDragging }"
            >
                <!-- 模态框头部（可拖动区域） -->
                <div 
                    class="fi-modal-header flex px-6 pt-6 cursor-move select-none"
                    @mousedown="startDrag($event)"
                >
                    <h2 class="fi-modal-heading text-base font-semibold leading-6 text-gray-950 dark:text-white pointer-events-none">
                        <span x-text="editingCommentId ? '编辑评论' : '添加评论'"></span>
                    </h2>
                    <button
                        type="button"
                        @click="closeModal()"
                        @mousedown.stop
                        class="fi-modal-close-btn -m-2 ms-auto flex h-8 w-8 items-center justify-center rounded-full text-gray-400 outline-none transition duration-75 hover:bg-gray-50 hover:text-gray-500 focus-visible:bg-gray-50 dark:hover:bg-white/5 dark:focus-visible:bg-white/5 cursor-pointer"
                    >
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- 模态框内容 -->
                <div class="fi-modal-content px-6 py-6 space-y-6">
                    <!-- 昵称 -->
                    <div class="fi-fo-field-wrp">
                        <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
                            <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                <span class="text-danger-600 dark:text-danger-400">*</span>
                                昵称
                            </span>
                        </label>
                        <div class="mt-2">
                            <input
                                type="text"
                                x-model="formData.nickname"
                                placeholder="请输入昵称"
                                maxlength="20"
                                class="fi-input block w-full rounded-lg border-gray-300 shadow-sm outline-none transition duration-75 focus:border-primary-600 focus:ring-1 focus:ring-inset focus:ring-primary-600 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] disabled:bg-gray-50 disabled:cursor-not-allowed dark:border-white/10 dark:bg-white/5 dark:focus:border-primary-600 dark:disabled:border-white/5 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:bg-transparent text-base sm:text-sm sm:leading-6 px-3 py-2"
                                :class="{ 'border-danger-600 focus:border-danger-600 focus:ring-danger-600': errors.nickname }"
                            >
                            <div class="flex justify-between items-center gap-x-3 mt-2">
                                <p x-show="errors.nickname" class="fi-fo-field-wrp-error-message text-sm text-danger-600 dark:text-danger-400" x-text="errors.nickname"></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 ml-auto">
                                    <span x-text="formData.nickname.length"></span> / 20
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 评论内容 -->
                    <div class="fi-fo-field-wrp">
                        <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
                            <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                <span class="text-danger-600 dark:text-danger-400">*</span>
                                评论
                            </span>
                        </label>
                        <div class="mt-2">
                            <textarea
                                x-model="formData.content"
                                placeholder="请输入评论，字符在5-500个之间"
                                rows="4"
                                maxlength="500"
                                class="fi-input block w-full rounded-lg border-gray-300 shadow-sm outline-none transition duration-75 focus:border-primary-600 focus:ring-1 focus:ring-inset focus:ring-primary-600 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] disabled:bg-gray-50 disabled:cursor-not-allowed dark:border-white/10 dark:bg-white/5 dark:focus:border-primary-600 dark:disabled:border-white/5 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:bg-transparent text-base sm:text-sm sm:leading-6 px-3 py-2 resize-none"
                                :class="{ 'border-danger-600 focus:border-danger-600 focus:ring-danger-600': errors.content }"
                            ></textarea>
                            <div class="flex justify-between items-center gap-x-3 mt-2">
                                <p x-show="errors.content" class="fi-fo-field-wrp-error-message text-sm text-danger-600 dark:text-danger-400" x-text="errors.content"></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 ml-auto">
                                    <span x-text="formData.content.length"></span> / 500
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 语言 -->
                    <div class="fi-fo-field-wrp">
                        <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
                            <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                <span class="text-danger-600 dark:text-danger-400">*</span>
                                语言
                            </span>
                        </label>
                        <div class="mt-2">
                            <select
                                x-model="formData.language_id"
                                class="fi-select-input block w-full rounded-lg border-gray-300 shadow-sm outline-none transition duration-75 focus:border-primary-600 focus:ring-1 focus:ring-inset focus:ring-primary-600 disabled:text-gray-500 disabled:bg-gray-50 disabled:cursor-not-allowed dark:border-white/10 dark:bg-gray-900 dark:text-white dark:focus:border-primary-600 dark:disabled:border-white/5 dark:disabled:bg-transparent text-base sm:text-sm sm:leading-6 pe-9 ps-3 py-2 [&>option]:dark:bg-gray-900 [&>option]:dark:text-white"
                                :class="{ 'border-danger-600 focus:border-danger-600 focus:ring-danger-600': errors.language_id }"
                            >
                                <option value="" class="dark:bg-gray-900 dark:text-gray-400">请选择语言</option>
                                @foreach($languages as $language)
                                    <option value="{{ $language->id }}" class="dark:bg-gray-900 dark:text-white">{{ $language->name }}</option>
                                @endforeach
                            </select>
                            <p x-show="errors.language_id" class="fi-fo-field-wrp-error-message text-sm text-danger-600 dark:text-danger-400 mt-2" x-text="errors.language_id"></p>
                        </div>
                    </div>
                </div>

                <!-- 模态框底部按钮 -->
                <div class="fi-modal-footer flex flex-row-reverse gap-3 px-6 py-6">
                    <button
                        type="button"
                        @click="saveComment"
                        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-primary fi-btn-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm shadow-sm inline-grid bg-primary-600 text-white hover:bg-primary-500 focus-visible:ring-primary-500/50 dark:bg-primary-500 dark:hover:bg-primary-400 dark:focus-visible:ring-primary-400/50"
                    >
                        确定
                    </button>
                    <button
                        type="button"
                        @click="closeModal()"
                        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-gray fi-btn-color-gray fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm shadow-sm inline-grid bg-white text-gray-950 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 ring-1 ring-gray-950/10 dark:ring-white/20"
                    >
                        取消
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

