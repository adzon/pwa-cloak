<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Comment;
use App\Models\Language;

class CommentsManager extends Component
{
    public $showModal = false;
    public $commentId;
    public $nickname;
    public $content;
    public $language_id;
    public $languageIdContext;

    protected $listeners = [
        'openCommentModal' => 'openModal',
        'editComment' => 'edit',
        'deleteComment' => 'delete',
    ];

    public function openModal($languageId)
    {
        $this->reset(['commentId', 'nickname', 'content', 'language_id']);
        $this->languageIdContext = $languageId;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $comment = Comment::findOrFail($id);
        $this->commentId = $id;
        $this->nickname = $comment->nickname;
        $this->content = $comment->content;
        $this->language_id = $comment->language_id;
        $this->showModal = true;
    }

    public function delete($id)
    {
        Comment::findOrFail($id)->delete();
        session()->flash('message', '评论已删除。');
    }

    public function save()
    {
        $this->validate([
            'nickname' => 'required|string|max:20',
            'content' => 'required|string|min:5|max:500',
            'language_id' => 'required|exists:languages,id',
        ]);

        Comment::updateOrCreate(
            ['id' => $this->commentId],
            [
                'nickname' => $this->nickname,
                'content' => $this->content,
                'language_id' => $this->language_id,
            ]
        );

        $this->showModal = false;
        $this->emitSelf('$refresh');
    }

    public function render()
    {
        return view('livewire.comments-manager', [
            'languages' => Language::all(),
            'comments' => Comment::where('language_id', $this->languageIdContext)->get(),
        ]);
    }
}
