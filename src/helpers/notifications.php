<?php

use App\Services\NotificationService;

/**
 * Send notification for new post
 */
function notifyNewPost($postId, $postTitle, $authorId = null)
{
    try {
        $notificationService = new NotificationService();
        
        $title = 'Post Baru';
        $message = $postTitle;
        
        $data = [
            'type' => 'new_post',
            'post_id' => $postId,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ];
        
        if ($authorId) {
            // Notify all users except the author
            // You'll need to implement getUsersToNotify in your User model
            // $users = User::getUsersToNotify($authorId);
            // foreach ($users as $user) {
            //     $notificationService->sendToUser($user->id, $title, $message, $data);
            // }
        } else {
            // Notify all users
            $notificationService->sendToAll($title, $message, $data);
        }
        
        return true;
    } catch (Exception $e) {
        error_log('Error in notifyNewPost: ' . $e->getMessage());
        return false;
    }
}

/**
 * Send notification for new announcement
 */
function notifyNewAnnouncement($announcementId, $announcementTitle)
{
    try {
        $notificationService = new NotificationService();
        
        $title = 'Pengumuman Baru';
        $message = $announcementTitle;
        
        $data = [
            'type' => 'new_announcement',
            'announcement_id' => $announcementId,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ];
        
        // Notify all users
        $notificationService->sendToAll($title, $message, $data);
        
        return true;
    } catch (Exception $e) {
        error_log('Error in notifyNewAnnouncement: ' . $e->getMessage());
        return false;
    }
}
