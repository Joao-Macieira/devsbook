<?php
namespace src\handlers;
use \src\models\Post;
use \src\models\PostComment;
use \src\models\PostLike;
use \src\models\User;
use \src\models\UserRelation;

class PostHandler {

    public static function addPost($idUser, $type, $body) {
        $body = trim($body);

        if(!empty($idUser) && !empty($body)) {
            
            Post::insert([
                'id_user' => $idUser,
                'type' => $type,
                'created_at' => date('Y-m-d H:i:s'),
                'body' => $body
            ])->execute();
        }
    }

    public static function delete($idPost, $idUser) {
        // 1. Verify post exist and is mine

        $post = Post::select()
            ->where('id', $idPost)
            ->where('id_user', $idUser)
        ->get();

        if(count($post) > 0) {
            $post = $post[0];

            // 2. Delete likes and comments
            PostLike::delete()->where('id_post', $idPost)->execute();
            PostComment::delete()->where('id_post', $idPost)->execute();

            // 3. If this post is a photo, delete the file
            if($post['type'] === 'photo') {
                $img = 'media/uploads/'.$post['body'];
                if(file_exists($img)) {
                    unlink($img);
                }
            }

            // 4. Delete post
            Post::delete()->where('id', $idPost)->Execute();
        }
    }   

    public static function _postListToObject($postList, $loggedUserId) {
        $posts = [];

        foreach($postList as $postItem) {
            $newPost = new Post();
            $newPost->id = $postItem['id'];
            $newPost->type = $postItem['type']; 
            $newPost->created_at = $postItem['created_at']; 
            $newPost->body = $postItem['body'];
            $newPost->mine = false;

            if($postItem['id_user'] == $loggedUserId) {
                $newPost->mine = true;
            }
            
            // 4. Preencher as informações adicionais.

            $newUser = User::select()->where('id', $postItem['id_user'])->one();

            $newPost->user = new User();
            $newPost->user->id = $newUser['id'];
            $newPost->user->name = $newUser['name'];
            $newPost->user->avatar = $newUser['avatar'];

            // 4.1 Preencher informações de likes

            $likes = PostLike::select()->where('id_post', $postItem['id'])->get();

            $newPost->likeCount = count($likes);
            $newPost->liked = self::isLiked($postItem['id'], $loggedUserId);

            // 4.2 Preencher informações de comments (TODO:)

            $newPost->comments = PostComment::select()->where('id_post', $postItem['id'])->get();
            foreach($newPost->comments as $key => $comment) {
                $newPost->comments[$key]['user'] = User::select()->where('id', $comment['id_user'])->one();
            }

            $posts[] = $newPost;
        }

        return $posts;
    }

    public static function isLiked($id, $loggedUserId) {
        $myLike = PostLike::select()
            ->where('id_post', $id)
            ->where('id_user', $loggedUserId)
        ->get();

        if(count($myLike) > 0) {
            return true;
        }

        return false;
    }

    public static function deleteLike($id, $loggedUserId) {
        PostLike::delete()
                ->where('id_post', $id)
                ->where('id_user', $loggedUserId)
            ->execute();
    }

    public static function addLike($id, $loggedUserId) {
        PostLike::insert([
                    'id_post' => $id,
                    'id_user' => $loggedUserId,
                    'created_at' => date('Y-m-d H:i:s')
                ])
            ->execute();
    }

    public static function addComment($id, $txt, $idUser) {
        PostComment::insert([
            'id_post' => $id,
            'id_user' => $idUser,
            'created_at' => date('Y-m-d H:i:s'), 
            'body' => $txt
        ])->execute();
    }

    public static function getHomeFeed($idUser, $page) {
        $perPage = 2;

        // 1. Pegar a lista de usuários que EU sigo.

        $userList = UserRelation::select()->where('user_from', $idUser)->get();

        $users = [];
        foreach($userList as $userItem) {
            $users[] = $userItem['user_to'];
        }

        $users[] = $idUser;

        // 2. Pegar os posts desses usuarios ordenado pela data.

        $postList = Post::select()
            ->where('id_user', 'in', $users)
            ->orderBy('created_at', 'desc')
            ->page($page, $perPage)
        ->get();

        $total = Post::select()
            ->where('id_user', 'in', $users)
        ->count();

        $pageCount = ceil($total / $perPage);

        // 3. Transformar o resultado em objetos dos models.

        $posts = self::_postListToObject($postList, $idUser);
        // 5. Retornar o resultado.

        return [
            'posts' => $posts,
            'pageCount' => $pageCount,
            'currentPage' => $page
        ];
    }

    public static function getUserFeed($idUser, $page, $loggedUserId) {
        // 2. Pegar os posts desses usuarios ordenado pela data.

        $perPage = 2;

        $postList = Post::select()
            ->where('id_user', $idUser)
            ->orderBy('created_at', 'desc')
            ->page($page, $perPage)
        ->get();

        $total = Post::select()
            ->where('id_user', $idUser)
        ->count();

        $pageCount = ceil($total / $perPage);

        // 3. Transformar o resultado em objetos dos models.

        $posts = self::_postListToObject($postList, $loggedUserId);

        // 5. Retornar o resultado.

        return [
            'posts' => $posts,
            'pageCount' => $pageCount,
            'currentPage' => $page
        ];
    }

    public static function getPhotosFrom($id_user) {
        $photosData = Post::select()
            ->where('id_user', $id_user)
            ->where('type', 'photo')
        ->get();

        $photos = [];

        foreach($photosData as $photo) {
            $newPost = new Post();
            $newPost->id = $photo['id'];
            $newPost->type = $photo['type'];
            $newPost->created_at = $photo['created_at'];
            $newPost->body = $photo['body'];

            $photos[] = $newPost;
        }

        return $photos;
    }
}