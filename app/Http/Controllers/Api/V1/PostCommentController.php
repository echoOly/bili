<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use League\Fractal\Pagination\Cursor;
use App\Transformers\PostCommentTransformer;
use App\Repositories\Contracts\PostRepository;
use App\Repositories\Contracts\PostCommentRepository;

class PostCommentController extends BaseController
{
    protected $postRepository;

    protected $postCommentRepository;

    public function __construct(PostCommentRepository $postCommentRepository, PostRepository $postRepository)
    {
        $this->postCommentRepository = $postCommentRepository;

        $this->postRepository = $postRepository;
    }

    /**
     * @api {get} /posts/{postId}/comments 评论列表(post comment list)
     * @apiDescription 评论列表(post comment list)
     * @apiGroup post_comment
     * @apiPermission none
     * @apiParam {String='user'} include  include
     * @apiVersion 0.1.0
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *    "data": [
     *      {
     *        "id": 1,
     *        "post_id": 1,
     *        "user_id": 1,
     *        "reply_user_id": 0,
     *        "content": "foobar",
     *        "created_at": "2016-04-06 14:51:34",
     *        "user": {
     *          "data": {
     *            "id": 1,
     *            "email": "foo@bar.com",
     *            "name": "foobar",
     *            "avatar": "",
     *            "created_at": "2016-01-28 07:23:37",
     *            "updated_at": "2016-01-28 07:24:05",
     *            "deleted_at": null
     *          }
     *        }
     *      },
     *      {
     *        "id": 2,
     *        "post_id": 1,
     *        "user_id": 1,
     *        "reply_user_id": 0,
     *        "content": "foobar1",
     *        "created_at": "2016-04-06 15:10:22",
     *        "user": {
     *          "data": {
     *            "id": 1,
     *            "email": "foo@bar.com",
     *            "name": "foobar",
     *            "avatar": "",
     *            "created_at": "2016-01-28 07:23:37",
     *            "updated_at": "2016-01-28 07:24:05",
     *            "deleted_at": null
     *          }
     *        }
     *      },
     *      {
     *        "id": 3,
     *        "post_id": 1,
     *        "user_id": 1,
     *        "reply_user_id": 0,
     *        "content": "foobar2",
     *        "created_at": "2016-04-06 15:10:23",
     *        "user": {
     *          "data": {
     *            "id": 1,
     *            "email": "foo@bar.com",
     *            "name": "foobar",
     *            "avatar": "",
     *            "created_at": "2016-01-28 07:23:37",
     *            "updated_at": "2016-01-28 07:24:05",
     *            "deleted_at": null
     *          }
     *        }
     *      }
     *    ],
     *    "meta": {
     *      "pagination": {
     *        "total": 3,
     *        "count": 3,
     *        "per_page": 15,
     *        "current_page": 1,
     *        "total_pages": 1,
     *        "links": []
     *      }
     *    }
     *  }
     */
    public function index($postId, Request $request)
    {
        $post = $this->postRepository->find($postId);

        if (! $post) {
            return $this->response->errorNotFound();
        }

        $comments = $this->postCommentRepository->where(['post_id' => $postId]);

        $currentCursor = $request->get('cursor');

        if ($currentCursor !== null) {
            $currentCursor = (int) $request->get('cursor', null);
            // how to use previous ??
            // $prevCursor = $request->get('previous', null);
            $limit = $request->get('limit', 10);

            $comments = $comments->where([['id', '>', $currentCursor]])->limit($limit)->get();

            $nextCursor = $comments->last()->id;
            $prevCursor = $currentCursor;
            $cursor = new Cursor($currentCursor, $nextCursor, $prevCursor, $comments->count());

            $cursorPatination = new Cursor($currentCursor, $prevCursor, $nextCursor, $comments->count());

            return $this->response->collection($comments, new PostCommentTransformer(), [], function ($resource) use ($cursorPatination) {
                $resource->setCursor($cursorPatination);
            });
        } else {
            $comments = $comments->paginate();

            return $this->response->paginator($comments, new PostCommentTransformer());
        }
    }

    /**
     * @api {post} /posts/{postId}/comments 发布评论(create post comment)
     * @apiDescription 发布评论(create post comment)
     * @apiGroup post_comment
     * @apiPermission jwt
     * @apiParam {String} content  post content
     * @apiVersion 0.1.0
     * @apiSuccessExample {json} Success-Response:
     * HTTP/1.1 201 Created
     * {
     *     "data": {
     *         "content": "bar",
     *         "user_id": 33,
     *         "post_id": "211",
     *         "created_at": "2017-01-18 15:44:08",
     *         "id": 1102
     *     }
     * }
     */
    public function store($postId, Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorBadRequest($validator);
        }

        $post = $this->postRepository->find($postId);

        if (! $post) {
            return $this->response->errorNotFound();
        }

        $user = $this->user();

        $attributes = $request->only('content');
        $attributes['user_id'] = $user->id;
        $attributes['post_id'] = $postId;

        $comment = $this->postCommentRepository->create($attributes);

        // return $this->response->created($location);

        // 返回数据
        return $this->response
            ->item($comment, new PostCommentTransformer())
            ->setStatusCode(201);
    }

    /**
     * @api {delete} /posts/{postId}/comments/{id} 删除评论(delete post comment)
     * @apiDescription 删除评论(delete post comment)
     * @apiGroup post_comment
     * @apiPermission jwt
     * @apiVersion 0.1.0
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 204 NO CONTENT
     */
    public function destroy($postId, $id)
    {
        $user = $this->user();

        $comment = $this->postCommentRepository
            ->where(['post_id' => $postId, 'user_id' => $user->id])
            ->find($id);

        if (! $comment) {
            return $this->response->errorNotFound();
        }

        $this->postCommentRepository->destroy($id);

        return $this->response->noContent();
    }
}
