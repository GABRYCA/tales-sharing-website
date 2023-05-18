<?php
include_once (dirname(__FILE__) . "/../connection.php");

class Content
{
    private $contentId;
    private $ownerId;
    private $type;
    private $urlImage;
    private $textContent;
    private $title;
    private $description;
    private $uploadDate;
    private $privateOrPublic;
    private $isAI;
    private $errorStatus;

    /**
     * Function to load a content from the database by ID (please use setContentId() before).
     * @return bool
     */
    public function loadContent(): bool {
        $conn = connection();

        $sql = "SELECT * FROM Content WHERE contentId = ?";

        if ($data = $conn->execute_query($sql, [$this->contentId])) {
            $row = $data->fetch_assoc();
            $this->ownerId = $row["ownerId"];
            $this->type = $row["type"];
            $this->urlImage = $row["urlImage"];
            $this->textContent = $row["textContent"];
            $this->title = $row["title"];
            $this->description = $row["description"];
            $this->uploadDate = $row["uploadDate"];
            $this->privateOrPublic = $row["privateOrPublic"];
            $this->isAI = $row["isAI"];
            return true;
        } else {
            $this->setErrorStatus("Error while loading content");
            return false;
        }
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

        if ($conn->execute_query($sql, [$this->ownerId, $this->type, $this->urlImage, $this->textContent, $this->title, $this->description, $this->uploadDate, $this->privateOrPublic, $this->isAI])) {
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

        if ($conn->execute_query($sql, [$this->ownerId, $this->type, $this->urlImage, $this->textContent, $this->title, $this->description, $this->uploadDate, $this->privateOrPublic, $this->isAI, $this->contentId])) {
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

        $sql = "SELECT * FROM Content";

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

        $sql = "SELECT * FROM Content ORDER BY uploadDate DESC LIMIT ?";

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

        $sql = "SELECT * FROM Content WHERE isPrivate = 0";

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

        $sql = "SELECT * FROM Content WHERE isPrivate = 1";

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

        $sql = "SELECT * FROM Content WHERE isAI = 1";

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

        $sql = "SELECT * FROM Content WHERE isAI = 0";

        if ($data = $conn->execute_query($sql)) {
            return $this->contentDataArray($data);
        } else {
            $this->setErrorStatus("Error while getting all not AI generated content");
            return array();
        }
    }

    /**
     * Function to get all the content from the database that is public and not AI generated.
     * @return array
     */
    public function getAllPublicNotAIGeneratedContent(): array
    {
        $conn = connection();

        $sql = "SELECT * FROM Content WHERE isPrivate = 0 AND isAI = 0";

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
     * @return mixed
     */
    public function getPrivateOrPublic()
    {
        return $this->privateOrPublic;
    }

    /**
     * @param mixed $privateOrPublic
     */
    public function setPrivate($privateOrPublic): void
    {
        $this->privateOrPublic = $privateOrPublic;
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
            $content->setPrivate($row["privateOrPublic"]);
            $content->setIsAI($row["isAI"]);
            $contentArray[] = $content;
        }
        return $contentArray;
    }

}