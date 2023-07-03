<?php
include_once (dirname(__FILE__) . "/../connection.php");
include_once (dirname(__FILE__) . "/../objects/Tag.php");
include_once (dirname(__FILE__) . "/../objects/Likes.php");
include_once (dirname(__FILE__) . "/../objects/StatsForContent.php");
include_once (dirname(__FILE__) . "/../objects/Comment.php");
include_once (dirname(__FILE__) . "/../objects/Gallery.php");

class Content implements JsonSerializable
{
    private $contentId;
    private $ownerId;
    private $type;
    private $urlImage;
    private $textContent;
    private $title;
    private $description;
    private $uploadDate;
    private $isPrivate;
    private $isAI;
    private $tagList = [];
    private $errorStatus;

    /**
     * Function to load a content from the database by ID (please use setContentId() before).
     * @return bool
     */
    public function loadContent(): bool {
        $conn = connection();

        $sql = "SELECT * FROM Content WHERE contentId = ?";

        if ($data = $conn->execute_query($sql, [$this->contentId])) {
            // Check if found content
            if ($data->num_rows == 0) {
                $this->setErrorStatus("Error while getting content by owner and path, no content found");
                return false;
            }
            $row = $data->fetch_assoc();
            $this->ownerId = $row["ownerId"];
            $this->type = $row["type"];
            $this->urlImage = $row["urlImage"];
            $this->textContent = $row["textContent"];
            $this->title = $row["title"];
            $this->description = $row["description"];
            $this->uploadDate = $row["uploadDate"];
            $this->isPrivate = $row["isPrivate"];
            $this->isAI = $row["isAI"];
            return true;
        } else {
            $this->setErrorStatus("Error while loading content");
            return false;
        }
    }

    /**
     * Load content by owner and path. Please set ownerId and url/path before using this.
     * @return bool
     */
    public function loadContentByPath() : bool
    {
        $conn = connection();

        $sql = "SELECT * FROM Content WHERE ownerId = ? AND urlImage = ?";

        if ($data = $conn->execute_query($sql, [$this->ownerId, $this->urlImage])) {
            // Check if found content
            if ($data->num_rows == 0) {
                $this->setErrorStatus("Error while getting content by owner and path, no content found");
                return false;
            }
            $row = $data->fetch_assoc();
            $this->contentId = $row["contentId"];
            $this->type = $row["type"];
            $this->textContent = $row["textContent"];
            $this->title = $row["title"];
            $this->description = $row["description"];
            $this->uploadDate = $row["uploadDate"];
            $this->isPrivate = $row["isPrivate"];
            $this->isAI = $row["isAI"];
            return true;
        } else {
            $this->setErrorStatus("Error while getting content by owner and path");
        }
        return false;
    }

    /**
     * Function to add a content to the database (using parameters set into object).
     * @return bool
     */
    public function addContent(): bool
    {
        $conn = connection();

        $sql = "INSERT INTO Content (ownerId, type, urlImage, textContent, title, description, uploadDate, isPrivate, isAI) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        // Check if type is image or text, if not stop and send error.
        if ($this->type != "image" && $this->type != "text") {
            $this->setErrorStatus("Error while adding content, type is not image or text");
            return false;
        }

        if ($conn->execute_query($sql, [$this->ownerId, $this->type, $this->urlImage, $this->textContent, $this->title, $this->description, $this->uploadDate, $this->isPrivate, $this->isAI])) {

            // If there're tags in tagList I load the content.
            if (count($this->tagList) == 0){
                return true;
            }

            $this->loadContentByPath(); // Load content by path.

            // Now, if there're, I can link the tags to the content.
            foreach ($this->tagList as $tag) {
                if (!$tag->addTagToContent($this->contentId)){
                    $this->setErrorStatus("Error while adding content, error while adding tag to content");
                    return false;
                }
            }

            return true;
        } else {
            $this->setErrorStatus("Error while adding content");
            return false;
        }
    }

    /**
     * Function to remove a content from the database.
     * @return bool
     */
    public function removeContent(): bool
    {
        $conn = connection();

        $sql = "DELETE FROM Content WHERE contentId = ?";

        if ($conn->execute_query($sql, [$this->contentId])) {
            return true;
        } else {
            $this->setErrorStatus("Error while removing content");
            return false;
        }
    }

    /**
     * Function to update a content from the database.
     * @return bool
     */
    public function updateContent(): bool
    {
        $conn = connection();

        $sql = "UPDATE Content SET ownerId = ?, type = ?, urlImage = ?, textContent = ?, title = ?, description = ?, uploadDate = ?, isPrivate = ?, isAI = ? WHERE contentId = ?";

        if ($conn->execute_query($sql, [$this->ownerId, $this->type, $this->urlImage, $this->textContent, $this->title, $this->description, $this->uploadDate, $this->isPrivate, $this->isAI, $this->contentId])) {
            return true;
        } else {
            $this->setErrorStatus("Error while updating content");
            return false;
        }
    }

    /**
     * Function to get all the content from the database.
     * @return array
     */
    public function getAllContent(): array
    {
        $conn = connection();

        $sql = "SELECT * FROM Content ORDER BY uploadDate DESC, contentId DESC";

        if ($data = $conn->execute_query($sql)) {
            return $this->contentDataArray($data);
        } else {
            $this->setErrorStatus("Error while getting all content");
            return array();
        }
    }

    /**
     * Function to get all the latest X amount of content from the database ordered by upload date (for example, get the latest 20 contents).
     * @param int $amount
     * @return array
     */
    public function getLatestContent(int $amount): array
    {
        $conn = connection();

        $sql = "SELECT * FROM Content ORDER BY uploadDate DESC, contentId DESC LIMIT ?";

        if ($data = $conn->execute_query($sql, [$amount])) {
            return $this->contentDataArray($data);
        } else {
            $this->setErrorStatus("Error while getting latest content");
            return array();
        }
    }

    /**
     * Function to get all the content from the database that is public.
     * @return array
     */
    public function getAllPublicContent(): array
    {
        $conn = connection();

        $sql = "SELECT * FROM Content WHERE isPrivate = 0 ORDER BY uploadDate DESC, contentId DESC";

        if ($data = $conn->execute_query($sql)) {
            return $this->contentDataArray($data);
        } else {
            $this->setErrorStatus("Error while getting all public content");
            return array();
        }
    }

    /**
     * Function to get all the content from the database that is private.
     * @return array
     */
    public function getAllPrivateContent(): array
    {
        $conn = connection();

        $sql = "SELECT * FROM Content WHERE isPrivate = 1 ORDER BY uploadDate DESC, contentId DESC";

        if ($data = $conn->execute_query($sql)) {
            return $this->contentDataArray($data);
        } else {
            $this->setErrorStatus("Error while getting all private content");
            return array();
        }
    }

    /**
     * Function to get all the content from the database that is AI generated.
     * @return array
     */
    public function getAllAIGeneratedContent(): array
    {
        $conn = connection();

        $sql = "SELECT * FROM Content WHERE isAI = 1 ORDER BY uploadDate DESC, contentId DESC";

        if ($data = $conn->execute_query($sql)) {
            return $this->contentDataArray($data);
        } else {
            $this->setErrorStatus("Error while getting all AI generated content");
            return array();
        }
    }

    /**
     * Function to get all the content from the database that is not AI generated.
     * @return array
     */
    public function getAllNotAIGeneratedContent(): array
    {
        $conn = connection();

        $sql = "SELECT * FROM Content WHERE isAI = 0 ORDER BY uploadDate DESC, contentId DESC";

        if ($data = $conn->execute_query($sql)) {
            return $this->contentDataArray($data);
        } else {
            $this->setErrorStatus("Error while getting all not AI generated content");
            return array();
        }
    }

    /**
     * Function to get all the content from the database that is public and not AI generated.
     * @return Content[]
     */
    public function getAllPublicNotAIGeneratedContent(): array
    {
        $conn = connection();

        $sql = "SELECT * FROM Content WHERE isPrivate = 0 AND isAI = 0 ORDER BY uploadDate DESC, contentId DESC";

        if ($data = $conn->execute_query($sql)) {
            return $this->contentDataArray($data);
        } else {
            $this->setErrorStatus("Error while getting all public not AI generated content");
            return array();
        }
    }

    /**
     * Function to get all the content of a specific user from the database.
     * @param int $userId
     * @return array
     */
    public function getAllContentOfUser(int $userId): array
    {
        $conn = connection();

        $sql = "SELECT * FROM Content WHERE ownerId = ?";

        if ($data = $conn->execute_query($sql, [$userId])) {
            return $this->contentDataArray($data);
        } else {
            $this->setErrorStatus("Error while getting all content of user");
            return array();
        }
    }

    /**
     * Function to get content id using path (please setPath before using this).
     * @return bool
     */
    public function getContentIdFromPath() : bool
    {
        $conn = connection();

        $sql = "SELECT contentId FROM Content WHERE urlImage = ?";

        if ($data = $conn->execute_query($sql, [$this->urlImage])) {
            // Check if found
            if ($data->num_rows > 0) {
                $this->setErrorStatus("Error while getting content id from path");
                return false;
            }
            $this->contentId = $data[0]["contentId"];
            return true;
        } else {
            $this->setErrorStatus("Error while getting content id from path");
        }
        return false;
    }

    /**
     * Function to get tags of content using content id (please setContentId before using this).
     * @return Tag[]
     */
    public function getTagsOfContent() : array
    {
        $tag = new Tag();
        return $tag->getTagListByContentId($this->contentId);
    }

    /**
     * Function to get number of views of content using content id (please setContentId before using this).
     * @return int
     */
    public function getNumberOfViews() : int
    {
        $views = new StatsForContent();
        $views->setContentId($this->contentId);
        // Check if getCounter is null, if it's, return 1.
        $counter = $views->getSumCounter();
        if ($counter == null) {
            return 1;
        }
        return $counter;
    }

    /**
     * Function to increment the counter of views of content using content id and given parameter $viewerId and optional ViewerIP (please setContentId before using this).
     * @param string $viewerId
     * @param string $viewerIP
     * @return bool
     * @throws Exception
     */
    public function incrementNumberOfViews(string $viewerId, string $viewerIP = "0.0.0.0") : bool
    {
        $views = new StatsForContent();
        $views->setContentId($this->contentId);
        $views->setViewerId($viewerId);
        $views->setViewerIP($viewerIP);
        return $views->incrementCounter();
    }

    /**
     * Function to get all comments of content using content id (please setContentId before using this).
     * @return Comment[]
     */
    public function getCommentsOfContent() : array
    {
        $comment = new Comment();
        $comment->setContentId($this->contentId);
        return $comment->getComments();
    }

    /**
     * Function that returns the number of comments of a content using content id (please setContentId before using this).
     * @return int
     */
    public function getNumberOfComments() : int
    {
        $comment = new Comment();
        $comment->setContentId($this->contentId);
        return count($comment->getComments());
    }

    /**
     * Function to add a comment to the contentId using parameters $userId, $commentText (please setContentId before using this).
     * @param string $userId
     * @param string $commentText
     * @return bool
     */
    public function addCommentToContent(string $userId, string $commentText) : bool
    {
        $comment = new Comment();
        $comment->setContentId($this->contentId);
        $comment->setUserId($userId);
        $comment->setCommentText($commentText);
        return $comment->addComment();
    }

    /**
     * Function to delete a comment from the contentId using parameters $commentId (please setContentId before using this).
     * @param string $commentId
     * @return bool
     */
    public function deleteCommentFromContent(string $commentId) : bool
    {
        $comment = new Comment();
        $comment->setCommentId($commentId);
        return $comment->deleteComment();
    }

    /**
     * Function to edit a comment from the contentId using parameters $commentId, $commentText, $userId (please setContentId before using this).
     * UserId is used to check if the user is the owner of the comment.
     * @param string $commentId
     * @param string $commentText
     * @param string $userId
     * @return bool
     */
    public function editCommentOfContent(string $commentId, string $commentText, string $userId) : bool
    {
        $comment = new Comment();
        $comment->setCommentId($commentId);
        // Load comment.
        if (!$comment->loadComment()) {
            $this->setErrorStatus("Error while loading comment");
            return false;
        }
        // Check if user is owner of comment.
        if ($comment->getUserId() != $userId) {
            $this->setErrorStatus("User is not owner of comment");
            return false;
        }
        $comment->setCommentText($commentText);
        return $comment->updateComment();
    }

    /**
     * Function to get all likes of content using content id (please setContentId before using this).
     * @return Likes[]
     */
    public function getLikesOfContent() : array
    {
        $like = new Likes();
        $like->setContentId($this->contentId);
        return $like->getLikesInternal();
    }

    /**
     * Function that returns the number of likes (please setContentId before using this).
     * @return int
     */
    public function getNumberOfLikes() : int
    {
        $like = new Likes();
        $like->setContentId($this->contentId);
        return count($like->getLikesInternal());
    }

    /**
     * Function to like the content given $userId (please setContentId before using this).
     * @param string $userId
     * @return bool
     */
    public function likeContent(string $userId) : bool
    {
        $like = new Likes();
        $like->setContentId($this->contentId);
        $like->setUserId($userId);
        return $like->addLikeInternal();
    }

    /**
     * Function to unlike the content given $userId (please setContentId before using this).
     * @param string $userId
     * @return bool
     */
    public function unlikeContent(string $userId) : bool
    {
        $like = new Likes();
        $like->setContentId($this->contentId);
        $like->setUserId($userId);
        return $like->removeLikeInternal();
    }

    /**
     * Function that reverse a like if there's already given $userId. (please setContentId before using this).
     * @param string $userId
     * @return bool
     */
    public function reverseLike(string $userId) : bool
    {
        $like = new Likes();
        $like->setContentId($this->contentId);
        $like->setUserId($userId);
        return $like->reverseLikeInternal();
    }

    /**
     * Function to check if the content is liked by the user given $userId (please setContentId before using this).
     * @param string $userId
     * @return bool
     */
    public function isLikedByUser(string $userId) : bool
    {
        $like = new Likes();
        $like->setContentId($this->contentId);
        $like->setUserId($userId);
        return $like->isLikedInternal();
    }

    /**
     * Function to add tag to content using content id and $tagId (please setContentId before using this).
     * @param int $tagId
     * @return bool
     */
    public function addTagToContent(int $tagId) : bool
    {
        $tag = new Tag();
        $tag->setTagId($tagId);
        return $tag->addTagToContent($this->contentId);
    }

    /**
     * Function to remove tag from content using content id and $tagId (please setContentId before using this).
     * @param int $tagId
     * @return bool
     */
    public function removeTagFromContent(int $tagId) : bool
    {
        $tag = new Tag();
        $tag->setTagId($tagId);
        return $tag->removeTagFromContent($this->contentId);
    }

    /**
     * @return mixed
     */
    public function getContentId()
    {
        return $this->contentId;
    }

    /**
     * @param mixed $contentId
     */
    public function setContentId($contentId): void
    {
        $this->contentId = $contentId;
    }

    /**
     * @return mixed
     */
    public function getOwnerId()
    {
        return $this->ownerId;
    }

    /**
     * @param mixed $ownerId
     */
    public function setOwnerId($ownerId): void
    {
        $this->ownerId = $ownerId;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getUrlImage()
    {
        return $this->urlImage;
    }

    /**
     * @param mixed $urlImage
     */
    public function setUrlImage($urlImage): void
    {
        $this->urlImage = $urlImage;
    }

    /**
     * @return mixed
     */
    public function getTextContent()
    {
        return $this->textContent;
    }

    /**
     * @param mixed $textContent
     */
    public function setTextContent($textContent): void
    {
        $this->textContent = $textContent;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title): void
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getUploadDate()
    {
        return $this->uploadDate;
    }

    /**
     * @param mixed $uploadDate
     */
    public function setUploadDate($uploadDate): void
    {
        $this->uploadDate = $uploadDate;
    }

    /**
     * @return bool
     */
    public function getIsPrivate() : bool
    {
        return $this->isPrivate;
    }

    /**
     * @param mixed $privateOrPublic
     */
    public function setPrivate($privateOrPublic): void
    {
        $this->isPrivate = $privateOrPublic;
    }

    /**
     * @return mixed
     */
    public function getErrorStatus()
    {
        return $this->errorStatus;
    }

    /**
     * @param mixed $errorStatus
     */
    public function setErrorStatus($errorStatus): void
    {
        $this->errorStatus = $errorStatus;
    }

    /**
     * @return mixed
     */
    public function getIsAI()
    {
        return $this->isAI;
    }

    /**
     * @param mixed $isAI
     */
    public function setIsAI($isAI): void
    {
        $this->isAI = $isAI;
    }

    /**
     * Function to set tags to content using content id (please setContentId before using this).
     * array of tags
     * @param array $tags
     * @return bool
     */
    public function setTags(array $tags) : bool
    {
        // For each tag, create a new tag object and add it to the taglist.
        foreach ($tags as $tag) {
            $tagObject = new Tag();
            // Set tag name.
            $tagObject->setName($tag);
            // Check if tag exists.
            if (!$tagObject->addTagIfNotExists()){
                $this->setErrorStatus("Error while adding tag to database");
                return false;
            }
            // Add tag to array internal (will be saved later).
            $this->tagList[] = $tagObject;
        }

        return true;
    }

    /**
     * @param mysqli_result|bool $data
     * @return array
     */
    private function contentDataArray(mysqli_result|bool $data): array
    {
        $contentArray = array();
        while ($row = $data->fetch_assoc()) {
            $content = new Content();
            $content->setContentId($row["contentId"]);
            $content->setOwnerId($row["ownerId"]);
            $content->setType($row["type"]);
            $content->setUrlImage($row["urlImage"]);
            $content->setTextContent($row["textContent"]);
            $content->setTitle($row["title"]);
            $content->setDescription($row["description"]);
            $content->setUploadDate($row["uploadDate"]);
            $content->setPrivate($row["isPrivate"]);
            $content->setIsAI($row["isAI"]);
            $contentArray[] = $content;
        }
        return $contentArray;
    }

    // Implements JsonSerializable
    public function jsonSerialize()
    {
        return [
            'contentId' => $this->contentId,
            'ownerId' => $this->ownerId,
            'type' => $this->type,
            'urlImage' => $this->urlImage,
            'textContent' => $this->textContent,
            'title' => $this->title,
            'description' => $this->description,
            'uploadDate' => $this->uploadDate,
            'isPrivate' => $this->isPrivate,
            'isAI' => $this->isAI,
            'errorStatus' => $this->errorStatus
        ];
    }
}