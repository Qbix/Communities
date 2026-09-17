# Communities Plugin — LLM Coding Primer

Supplement to Q Framework, Users, Streams, Places, Calendars, and Assets primers.
Covers community lifecycle, dashboard navigation, event/service aggregation,
profile management, conversations, onboarding, and user import.

---

## 1. Community Lifecycle

```php
// Create a new community
$result = Communities::create('My Community', array(
    'skipAccess'       => false,    // check permissions and quotas
    'throwIfExist'     => true,
    'creditsConfirmed' => null,     // null = prompt user, true = charge
    'username'         => 'my-community',
    'xids'             => array()   // external platform IDs
));
// Creates Users_User with generated ID
// Calls prepareCommunity() → creates username, experience/main, locations streams
// Adds standard labels, sets creator as Users/owners
// Returns Users_User (or array with 'request' key if credits confirmation needed)

// Prepare an existing community (idempotent setup)
$streams = Communities::prepareCommunity($communityId);
// Returns: {usernameStream, experienceStream, locationsStream}
// Creates Streams/user/username, Streams/experience/main, Places/user/locations
// Adds all labels from Users.roles config

// Switch active community
Communities::setCommunity($communityId, array(
    'subscribe'   => array('Streams/experience/main'), // auto-subscribe
    'skipAccess'  => false,
    'setLocation' => 'Places/user/location'  // copy community location to user
));
// Stores in $_SESSION['Users']['communityId'] and cookie
// Auto-joins user to Calendars/calendar/main

// Generate a community ID
$id = Communities::generateId();   // ucfirst'd unique ID from DB
$id = Communities::idFromName('My NYU Community'); // → 'MyNYUCommunity'

// Check permissions
$isAdmin = Communities::isAdmin($userId, $communityId);
$canCreate = Communities::canCreateCommunities($userId);
```

---

## 2. Dashboard & Tabs

```php
// Build dashboard navigation (for Q/tabs tool)
$menu = Communities::dashboardMenu();
// Returns: {tabs, urls, classes, attributes}
// Tabs from Communities.tabs config, filtered by login/admin status
// Default: community, people, events, media, discuss, me

// Tabs are configurable:
// Communities.tabs.events.admin = true    → only show to admins
// Communities.tabs.me.name = "My Stuff"   → custom label
// Communities.tabs.media.uri = "Media/channels" → custom route

// The "me" tab renders Users/status tool with avatar
// Each tab maps to a handler: {App}/{tab}/response/content
// Falls back to Communities/{tab}/response/content
```

---

## 3. Events & Services

```php
// Fetch community events (aggregated from Calendars)
$relations = Communities::events(array(
    'communityId'  => $communityId,
    'experienceId' => 'main',
    'interest'     => 'Hiking',         // filter by interest
    'category'     => 'sports',         // filter by category
    'fromTime'     => time(),
    'toTime'       => time() + 30*86400,
    'offset'       => 0,
    'limit'        => 50,
    'skipEndedEvents' => true
), $streams);
// Queries Calendars/calendar/{experienceId} category
// Supports interest-based and location-based discovery
// Includes events where logged-in user is publisher
// $streams filled with public stream data

// Fetch services (Calendars/availability)
$relations = Communities::services(array(
    'communityId'  => $communityId,
    'experienceId' => 'main',
    'fromTime'     => time(),
    'limit'        => 50
), $streams);

// Default event time window
list($fromTime, $toTime) = Communities::defaultEventTimes();

// Check event creation permission
$authorized = Communities::newEventAuthorized($userId);
```

---

## 4. Conversations

```php
// Get community chat streams
$relations = Communities::conversationChats(
    $communityId,
    'main',        // experienceId
    0,             // offset
    50             // limit
);
// Fetches from Streams/chats/main category
// Filtered by Communities.conversations.relationTypes
// Default: Streams/chat, Websites/webpage

// Get chats the user is participating in
$relations = Communities::participatingChats(
    $communityId,
    'main',
    0,
    50
);
// Filters by user participation + sorted by updatedTime

// Get the chats category stream
$category = Communities::chatsMainCategory($communityId);
```

---

## 5. User Profiles & Social

```php
// Fetch social network streams for a user
$social = Communities::fetchSocialStreams($asUserId, $userId);
// Returns streams: Streams/user/twitter, /linkedin, /github, /facebook, /instagram

// Profile sections configured in Communities.profile:
// ordering: ["roles", "personal", "greeting", "social", "links", "jobs", "education"]
// sections: { personal: true, greeting: true, social: true, ... }
// social: { facebook: "facebook.com/", twitter: "twitter.com/", ... }
// tabs: { about: false, interests: true, chat: true, gallery: true }

// User streams for profile data:
// Streams/user/jobs         — job history (text)
// Streams/user/education    — education history (text)
// Streams/user/{platform}   — social username (text/username type)
// Streams/greeting/{communityId} — per-community greeting
```

---

## 6. People Directory

```php
// Get community member user IDs
$userIds = Communities::userIds(array(
    'limit'              => 100,
    'customIconsFirst'   => true,   // users with custom avatars first
    'includeFutureUsers' => false
));

// Check if a user ID is a community
$isCommunity = Communities::isCommunityId($communityId);

// Get all communities in the system
$communities = Communities::getAllCommunities($withMainCommunity);
// Returns array of Users_User objects for community accounts
```

---

## 7. User Import

```php
// Grant/revoke admin access for imported users
Communities_Import::grantUserAccess($userId, $adminUserId);
Communities_Import::denyUserAccess($userId, $adminUserId);

// CSV import fields:
// First Name, Last Name, Gender, Position, Interest, Label,
// Organization, State, Country, Email Address, Blog URL,
// Conversation URL, Photo URL, Cover URL,
// Facebook URL, Twitter URL, Linkedin URL, Github URL, Instagram URL

// Config: Communities.community.importUsers.image.removeBackground = true
// Uses AI_Image::removeBackground() on imported photos
```

---

## 8. Blocked Users

```php
// Get or create blocked users stream
$stream = Communities::getBlockedUsersStream();
// Returns Communities/user/blocked stream (Streams/resource type)
// Block/unblock managed via POST to Communities/usersBlock
```

---

## 9. Labels & Roles

```php
// Get all configured role labels
$labels = Communities::getLabels();
// Returns array of label strings from Users.roles config

// Add standard labels to a community
Communities::addCommunityLabels($communityId);
// Creates all labels from Users.roles config on the community

// Key config-driven access controls:
// Communities.community.admins          — who can manage community
// Communities.community.canInvite       — who can invite
// Communities.newEvent.authorized       — who can create events
// Communities.users.canImport           — who can import users
// Communities.articles.canManage        — who can manage articles
// Communities.occupants.canManage       — who can manage occupants
// Communities.locations.canRelate       — who can relate locations
// Communities.promote.labels            — who can promote content
```

---

## 10. Common Mistakes

| Wrong | Right |
|-------|-------|
| Creating community users with `Users_User` directly | Use `Communities::create()` — handles prepareCommunity, labels, quotas, permissions |
| Switching communities by setting session directly | Use `Communities::setCommunity()` — handles cookie, calendar join, location, subscriptions |
| Querying events from Calendars directly | Use `Communities::events()` — handles interest/location filtering, publisher inclusion, dedup |
| Building dashboard tabs manually | Use `Communities::dashboardMenu()` — handles admin filtering, URL resolution, active state |
| Hardcoding social network URLs | Use `Communities.profile.social` config — platform → URL prefix mapping |
| Checking admin status with raw Users::roles | Use `Communities::isAdmin()` — reads from `Communities.community.admins` config |
| Creating communities without quota check | `Communities::create()` enforces quotas and credit charges when `skipAccess=false` |

---

## 11. Configuration Reference

```
Communities.tabs.*                        — dashboard navigation tabs
Communities.community.admins              — admin role labels
Communities.community.canInvite           — who can send invites
Communities.community.tabs.*              — community management tabs
Communities.community.hideUntilParticipants — min members to show community
Communities.community.importUsers.*       — CSV import settings
Communities.me.tabs.*                     — "Me" page tabs
Communities.profile.ordering              — profile section order
Communities.profile.sections.*            — which sections to show
Communities.profile.social.*              — social network URL prefixes
Communities.profile.tabs.*                — profile view tabs
Communities.conversations.relationTypes   — chat relation types to show
Communities.pageSizes.*                   — pagination limits
Communities.people.userIds.*              — people directory settings
Communities.welcome.gallery              — welcome page Ken Burns gallery
Communities.terms.jurisdiction            — terms of service jurisdiction
Communities.dashboard.showLogin           — show login in dashboard
```