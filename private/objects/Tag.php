<?php
include_once (dirname(__FILE__) . "/../connection.php");
include_once (dirname(__FILE__) . "/../objects/Content.php");

class Tag implements JsonSerializable
{

    private $tagId = null;
    private $name = null;
    private $errorStatus = null;

    /**
     * Function to load tag info from database following $tagId.
     * @return bool
     */
    public function loadTag() : bool
    {
        $conn = connection();

        $sql = "SELECT * FROM Tag WHERE tagId = ?";

        if ($data = $conn->execute_query($sql, [$this->tagId])){
            $row = $data->fetch_assoc();
            $this->name = $row["name"];
        } else {
            $this->setErrorStatus("Error while loading tag");
            return false;
        }

        return true;
    }

    /**
     * Function to load tag info from database following $name.
     * @return bool
     */
    public function loadTagByName() : bool
    {
        $conn = connection();

        $sql = "SELECT * FROM Tag WHERE name = ?";

        if ($data = $conn->execute_query($sql, [$this->name])){
            $row = $data->fetch_assoc();
            $this->tagId = $row["tagId"];
        } else {
            $this->setErrorStatus("Error while loading tag");
            return false;
        }

        return true;
    }

    /**
     * Function that returns the full list of tags.
     * @return Tag[]
     */
    public function getTagList() : array
    {
        $conn = connection();

        $sql = "SELECT * FROM Tag ORDER BY name ASC";

        $tagList = [];

        if ($data = $conn->execute_query($sql)){
            while ($row = $data->fetch_assoc()){
                $tag = new Tag();
                $tag->setTagId($row["tagId"]);
                $tag->setName($row["name"]);
                $tagList[] = $tag;
            }
        }

        return $tagList;
    }

    /**
     * Function that returns the full list of tags for a specific content.
     * @return Tag[]
     */
    public function getTagListByContentId(int $contentId) : array
    {
        $conn = connection();

        $sql = "SELECT Tag.tagId, Tag.name FROM Tag INNER JOIN TagAssociation TA on Tag.tagId = TA.tagId WHERE TA.contentId = ? ORDER BY name ASC";

        $tagList = [];

        if ($data = $conn->execute_query($sql, [$contentId])){
            while ($row = $data->fetch_assoc()){
                $tag = new Tag();
                $tag->setTagId($row["tagId"]);
                $tag->setName($row["name"]);
                $tagList[] = $tag;
            }
        }

        return $tagList;
    }

    /**
     * Function that returns the full list of contents for a specific tagId.
     * @return Content[]
     */
    public function getContentListByTagId() : array
    {
        $conn = connection();

        $sql = "SELECT Content.contentId FROM Content INNER JOIN TagAssociation TA ON Content.contentId = TA.contentId WHERE TA.tagId = ? ORDER BY Content.uploadDate DESC";

        $contentList = [];

        if ($data = $conn->execute_query($sql, [$this->tagId])){
            while ($row = $data->fetch_assoc()){
                $content = new Content();
                $content->setContentId($row["contentId"]);
                $content->loadContent();
                $contentList[] = $content;
            }
        }

        return $contentList;
    }

    /**
     * Function that returns the full list of contents for a specific tag $name.
     * @return Content[]
     */
    public function getContentListByTagName() : array
    {
        $conn = connection();

        $sql = "SELECT Content.contentId FROM Content INNER JOIN TagAssociation TA ON Content.contentId = TA.contentId INNER JOIN Tag T ON TA.tagId = T.tagId WHERE T.name = ? ORDER BY Content.uploadDate DESC";

        $contentList = [];

        if ($data = $conn->execute_query($sql, [$this->name])){
            while ($row = $data->fetch_assoc()){
                $content = new Content();
                $content->setContentId($row["contentId"]);
                $content->loadContent();
                $contentList[] = $content;
            }
        }

        return $contentList;
    }

    /**
     * Add this tag to the database (please set name before).
     * @return bool
     */
    public function addTag() : bool
    {
        $conn = connection();

        $sql = "INSERT INTO Tag (name) VALUES (?)";

        if ($conn->execute_query($sql, [$this->name])){
            $this->tagId = $conn->insert_id;
        } else {
            $this->setErrorStatus("Error while adding tag");
            return false;
        }

        return true;
    }

    /**
     * Function to add a tag only if it doesn't exists, please set name before.
     * This also loads the tag after adding it.
     * @return bool
     */
    public function addTagIfNotExists() : bool
    {
        $conn = connection();

        $sql = "SELECT * FROM Tag WHERE name = ?";

        if ($data = $conn->execute_query($sql, [$this->name])){
            if ($data->num_rows == 0){
                if (!$this->addTag()){
                    return false;
                } else {
                    $this->loadTagByName();
                }
            } else {
                $row = $data->fetch_assoc();
                $this->tagId = $row["tagId"];
                $this->loadTag();
            }
        } else {
            $this->setErrorStatus("Error while adding tag");
            return false;
        }

        return true;
    }

    /**
     * Update this tag in the database (please set tagId and name before).
     * @return bool
     */
    public function updateTag() : bool
    {
        $conn = connection();

        $sql = "UPDATE Tag SET name = ? WHERE tagId = ?";

        if ($conn->execute_query($sql, [$this->name, $this->tagId])){
            return true;
        } else {
            $this->setErrorStatus("Error while updating tag");
            return false;
        }
    }

    /**
     * Delete this tag from the database (please set tagId before).
     * @return bool
     */
    public function deleteTag() : bool
    {
        $conn = connection();

        $sql = "DELETE FROM Tag WHERE tagId = ?";

        if ($conn->execute_query($sql, [$this->tagId])){
            return true;
        } else {
            $this->setErrorStatus("Error while deleting tag");
            return false;
        }
    }

    /**
     * Delete tag by name from the database (please set name before).
     * @return bool
     */
    public function deleteTagByName() : bool
    {
        $conn = connection();

        $sql = "DELETE FROM Tag WHERE name = ?";

        if ($conn->execute_query($sql, [$this->name])){
            return true;
        } else {
            $this->setErrorStatus("Error while deleting tag");
            return false;
        }
    }

    /**
     * Add a tag to a content (please set $tagId before).
     */
    public function addTagToContent(int $contentId) : bool
    {
        $conn = connection();

        $sql = "INSERT INTO TagAssociation (tagId, contentId) VALUES (?, ?)";

        if ($conn->execute_query($sql, [$this->tagId, $contentId])){
            return true;
        } else {
            $this->setErrorStatus("Error while adding tag to content");
            return false;
        }
    }

    /**
     * Remove a tag from a content (please set tagId before).
     */
    public function removeTagFromContent(int $contentId) : bool
    {
        $conn = connection();

        $sql = "DELETE FROM TagAssociation WHERE tagId = ? AND contentId = ?";

        if ($conn->execute_query($sql, [$this->tagId, $contentId])){
            return true;
        } else {
            $this->setErrorStatus("Error while removing tag from content");
            return false;
        }
    }

    /**
     * Remove all tags from a content (please set contentId before).
     */
    public function removeAllTagsFromContent() : bool
    {
        $conn = connection();

        $sql = "DELETE FROM TagAssociation WHERE contentId = ?";

        if ($conn->execute_query($sql, [$this->contentId])){
            return true;
        } else {
            $this->setErrorStatus("Error while removing all tags from content");
            return false;
        }
    }

    // Getter and setter functions
    public function getTagId() : int
    {
        return $this->tagId;
    }

    public function setTagId(int $tagId) : void
    {
        $this->tagId = $tagId;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    public function getErrorStatus() : string
    {
        return $this->errorStatus;
    }

    public function setErrorStatus(string $errorStatus) : void
    {
        $this->errorStatus = $errorStatus;
    }

    // Implements JsonSerializable
    public function jsonSerialize()
    {
        return [
            "tagId" => $this->tagId,
            "name" => $this->name
        ];
    }
}