<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InternalCommentAddedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $comment;

    /**
     * Create a new notification instance.
     */
    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        // Only use database notifications in development
        if (app()->environment('local', 'development')) {
            return ['database'];
        }

        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $reportable = $this->comment->commentable;
        $ticketNo = $reportable->ticket_no ?? 'N/A';

        return (new MailMessage)
            ->subject('[Internal Note] New Comment - '.$ticketNo)
            ->greeting('Hello '.$notifiable->name)
            ->line('A new internal note/comment has been added to ticket '.$ticketNo.'.')
            ->line('Comment by: '.($this->comment->user->name ?? 'Staff/Admin'))
            ->line('Content: '.substr($this->comment->content, 0, 100).'...')
            ->action('View Ticket Details', url('/admin/reports/'.($reportable->id ?? '')))
            ->line('This note is visible only to internal personnel.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $reportable = $this->comment->commentable;

        return [
            'type' => 'internal_comment_added',
            'comment_id' => $this->comment->id,
            'reportable_id' => $reportable->id ?? null,
            'ticket_no' => $reportable->ticket_no ?? 'N/A',
            'commenter_name' => $this->comment->user->name ?? 'Unknown',
            'comment_preview' => substr($this->comment->content, 0, 100),
            'is_internal' => true,
            'message' => 'Catatan internal baru pada '.($reportable->ticket_no ?? 'tiket').' oleh '.($this->comment->user->name ?? 'Staff/Admin'),
        ];
    }
}
