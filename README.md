# tales-sharing-website
Just an idea, maybe I'll get to work on it this summer, just to share stuff with some friends.

# Good points
Name: TalesAndArts - Short -> Tales
Domain: <a href="https://tales.anonymousgca.eu">tales.anonymousgca.eu</a>
Description: A website to share your stories, drawings, etc. with your friends.
Features:
- Share your stories, drawings, etc. with your friends.
- Group arts and stories in "universes" or "series" or "tales".
- Notifications on new arts and stories of followed users, comments, favourites and repost.
- Should be mobile friendly.
- Easy to use, better less functionalities for ease of use.
- Should have a simple backend, not scalable, just for a few users (if the project becomes more ambitious, maybe this will become a concern).
- Storage should be possible through direct links to images too (for example, you got a link to a picture on another art sharing website, you can paste it and will be visible),
This is meant for storage size concerns (I got only 50GB available on my server, and I don't want to pay for more).
- Should use a database, with paths to images stored on the file system or remotely, keeping it simple and universal.
- Should convert images to webp, and store them if uploaded directly and not linked.
- Images should be shown with a width and height of 100% of screen on mobile, on desktop should be a collection of images with a fixed width and height, but no scrollbar (some images will have different aspect ratios).
- Should be possible to register an account with a username, email and password.
- Should have a profile for each user, with a profile picture, a cover picture, a description, a list of followers and a list of following users.
- Should have a search bar, to search for users, universes, series, tales, etc.
- Should have a "feed" page, where you can see the latest arts and stories of the users you follow.
- Should have a "discover" page, where you can see the latest arts and stories of all users.
- Should have a "trending" page, where you can see the most popular arts and stories of all users.
- Should have a "popular" page, where you can see the most popular arts and stories of the users you follow.
- The profile user should be able to edit his profile, change his username, email, password, profile picture, cover picture, description, etc from a settings page.
- The profile should show your arts and stories, stored in universes, series and tales.
- Should have a "create universe" page, where you can create a universe, with a name, description, cover picture, etc.
- Should have a "create series" page, where you can create a series, with a name, description, cover picture, etc.
- Should have a "create tale" page, where you can create a tale, with a name, description, cover picture, etc.
- Should have a "create art" page, where you can upload an art, with a name, description, etc.
- Should have a "create story" page, where you can create a story, with a name, description, cover picture, etc.
- Should be possible to hide your arts to the public and make them private.
- The website should have public global stats, and private stats for each user.
- MAYBE but quite unlikely, an automatic AI filter to detect NSFW content, and hide it from the public.
- Should avoid the usage of cookies (2023 is coming, we don't need cookies anymore).
- Should have an option for each user to easily show or hide AI generated content, not everyone likes that.

# Planning:
### Problem: Me and my friends need a website where we can store and see each other stories and arts.
#### Future Vision: Availability to the public.

### Database (Requires revision):
- A table to store users, with username, male-female-unspecified, email, password (encrypted), URL to profile picture, URL to cover picture, description, motto.
- A Friend table, with each user id, and the id of the other user.
- A Follower table, with the id of the user, and the id of the follower.
- A Gallery group table, with the id of the owner, the id to an image table or text table, and an option to hide the gallery.
- A Content table, with the id of the owner, type (image or text), URL to image (Optional if text), text (Optional if image), title, description, upload date, private or public.
- A Liked or Favourite table, with the id of the user, and the id of the image or text.
- A Repost table, with the id of the user, and the id of the image or text.
- A Comment table, with the id of the user, the id of the image or text, the comment, the date.
- A Notification table, with the id of the user, the id of the image or text, the type of notification, the date, viewed or not.
- A Stats for Content table, with the id of the Content, and the id of the user who has seen it, if not available (not registered), store an optional IP.

### Possible preliminary commands to create the database (Requires revision):

```MariaDB
CREATE TABLE User (
    username varchar(255) PRIMARY KEY,
    gender varchar(11) CHECK (gender IN ('male', 'female', 'unspecified')) default 'unspecified',
    email varchar(255) UNIQUE NOT NULL,
    password varchar(255) NOT NULL,
    urlProfilePicture varchar(255),
    urlCoverPicture varchar(255),
    description varchar(255),
    motto varchar(255),
    showNSFW TINYINT(1) NOT NULL DEFAULT 0,
    ofAge TINYINT(1) NOT NULL DEFAULT 1,
    isActivated TINYINT(1) NOT NULL DEFAULT 0,
    isMuted TINYINT(1) NOT NULL DEFAULT 0,
    activationCode varchar(50),
    joinDate date DEFAULT CURRENT_DATE
);

CREATE TABLE Friend (
  senderId varchar(255) REFERENCES User(username) ON DELETE CASCADE ON UPDATE CASCADE,
  receiverId varchar(255) REFERENCES User(username) ON DELETE CASCADE ON UPDATE CASCADE,
  accepted boolean NOT NULL DEFAULT 0,
  PRIMARY KEY (senderId, receiverId)
);

CREATE TABLE Follower (
  userId varchar(255) REFERENCES User(username) ON DELETE CASCADE ON UPDATE CASCADE,
  followerId varchar(255) REFERENCES User(username) ON DELETE CASCADE ON UPDATE CASCADE,
  PRIMARY KEY (userId, followerId)
);

CREATE TABLE Content (
  contentId int AUTO_INCREMENT PRIMARY KEY,
  ownerId varchar(255),
  type varchar(10),
  urlImage varchar(255),
  textContent varchar(255),
  title varchar(255),
  description varchar(255),
  uploadDate date NOT NULL,
  isPrivate TINYINT(1) NOT NULL DEFAULT 0,
  isAI TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (ownerId) REFERENCES User(username) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT type_check CHECK (type IN ('image', 'text'))
);

CREATE TABLE GalleryGroup (
  ownerId varchar(255) REFERENCES User(username) ON DELETE CASCADE ON UPDATE CASCADE,
  contentId int REFERENCES Content(contentId) ON DELETE CASCADE ON UPDATE CASCADE,
  hideGallery bit NOT NULL,
  PRIMARY KEY (ownerId, contentId)
);

CREATE TABLE Liked (
  userId varchar(255) REFERENCES User(username) ON DELETE CASCADE ON UPDATE CASCADE,
  contentId int REFERENCES Content(contentId) ON DELETE CASCADE ON UPDATE CASCADE,
  PRIMARY KEY (userId, contentId)
);

CREATE TABLE Repost (
  userId varchar(255) REFERENCES User(username) ON DELETE CASCADE ON UPDATE CASCADE,
  contentId int REFERENCES Content(contentId) ON DELETE CASCADE ON UPDATE CASCADE,
  PRIMARY KEY (userId, contentId)
);

CREATE TABLE Comment (
  userId varchar(255) REFERENCES User(username) ON DELETE CASCADE ON UPDATE CASCADE,
  contentId int REFERENCES Content(contentId) ON DELETE CASCADE ON UPDATE CASCADE,
  commentText varchar(255) NOT NULL,
  commentDate date NOT NULL
);

CREATE TABLE Notification (
  userId varchar(255) REFERENCES User(username) ON DELETE CASCADE ON UPDATE CASCADE,
  contentId int REFERENCES Content(contentId),
  notificationType varchar(20),
  notificationDate date NOT NULL,
  viewedOrNot bit NOT NULL
);

CREATE TABLE StatsForContent (
  contentId int REFERENCES Content(contentId) ON DELETE CASCADE ON UPDATE CASCADE,
  viewerId varchar(255),
  viewerIP varchar(15)
);

CREATE TABLE Premium (
  userid varchar(255) REFERENCES User(username) ON UPDATE CASCADE ON DELETE CASCADE,
  isPremium boolean DEFAULT false CHECK (isPremium IN (0,1)),
  subscriptionType varchar(255) DEFAULT 'plus' CHECK (subscriptionType IN ('plus', 'pro', 'premium')),
  subscriptionDate date DEFAULT CURRENT_DATE,
  expiryDate date DEFAULT DATE_ADD (current_date, INTERVAL 1 month)
);
```

### Diagram (Requires revision and may not be updated):

![Diagram.png](assets%2Fimg%2FDiagram.webp)
