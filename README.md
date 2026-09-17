# Communities Plugin

Ready-made application framework that ties together Users, Streams, Places, Calendars, Assets, Websites, Travel, and Media into a cohesive community platform. Instead of writing boilerplate for each app, Communities provides configurable pages, column navigation, dashboard menus, onboarding flows, profile pages, people directories, event listings, service bookings, conversations, and user import — all wired to the underlying plugins via config-driven routing and role-based access.

## Core Concepts

### Multi-Community Architecture

Communities supports multiple communities within a single deployment. Each community is a `Users_User` row with a generated ID (via `Communities::generateId()`). `Communities::create($communityName)` creates a community user, runs `prepareCommunity()` to set up essential streams (username, experience/main, Places/user/locations), adds standard role labels, and assigns the creating user as `Users/owners`.

`Communities::setCommunity($communityId)` switches the active community in the session and cookie (`Q_Users_communityId`), auto-subscribes the user to configured streams, optionally sets their location from a community location stream, and joins them to the community's `Calendars/calendar/main`.

Community creation is gated by `canCreateCommunities()` — checks admin status, a permissions stream (`Communities/create/permissions`), and quotas (default: 1 per week for regular users, 100 for admins). If quota is exceeded, can fall through to an Assets credits charge.

### Dashboard & Navigation

`Communities::dashboardMenu()` builds the tab navigation from `Communities.tabs` config. Default tabs: community, people, events, media, discuss, me. Each tab can be restricted to admins or calendar admins. The dashboard renders via `Q/response/dashboard` handler with `Q/tabs` tool.

The plugin defines an extensive route table (50+ routes) mapping clean URLs to column-based views: `/events`, `/event/{publisherId}/{eventId}`, `/people`, `/profile/{userId}`, `/conversations`, `/conversation/{publisherId}/{streamName}`, `/me`, `/community/{communityId}`, `/trip/{publisherId}/{tripId}`, `/webrtc/{publisherId}/{webrtcId}`, `/services`, `/welcome`, etc. Routes delegate to column handlers that build the Qbix column navigation UI (list → detail → sub-detail).

### People Directory

`Communities::userIds($options)` fetches community member user IDs with options for custom-icons-first sorting and future-user filtering. The people page shows community members using `Streams/related` with avatar previews. Demo user generation (`scripts/demo-users.php`) creates sample users with gendered placeholder face images for development.

### Events

`Communities::events($options)` aggregates events from the community's `Calendars/calendar/{experienceId}` category, filtered by time window, interest, category, or location. Supports location-based discovery via `Places_Interest::byTime()` and `Places_Nearby::byTime()`. Includes events where the logged-in user is the publisher. The "new event" flow presents the Calendars event composer with configurable location and teleconference options.

### Services

`Communities::services($options)` lists `Calendars/availability` streams related to the community, filtered similarly to events (by time, interest, location). Services represent bookable time slots backed by `Assets/service` templates.

### Conversations

`Communities::conversationChats()` fetches chat streams from the community's `Streams/chats/main` category, filtered by `Communities.conversations.relationTypes` (default: `Streams/chat` and `Websites/webpage`). `Communities::participatingChats()` returns chats the user is actively participating in. New conversations are created via the composer handler.

### User Profiles

Profile pages display configurable sections: personal info, greeting, roles, social links (Facebook, Twitter, LinkedIn, GitHub, Instagram, Telegram, Discord, YouTube), external links, jobs, education, and language. Section visibility is controlled by `Communities.profile.sections` config. Profile tabs include about, interests, chat, and gallery. Social network usernames are stored as `Streams/user/{platform}` streams.

### Onboarding

The onboarding flow guides new users through setup: setting location (via Places), selecting interests, and viewing community members. Triggered after registration or invite acceptance. The invite accept handler sets the community, subscribes to experience streams, and redirects to onboarding if configured.

### User Import

`Communities_Import` handles bulk user import from CSV with fields: First Name, Last Name, Gender, Position, Interest, Label, Organization, State, Country, Email Address, Blog URL, Conversation URL, Photo URL, Cover URL, and social profile URLs. Supports icon import with optional AI background removal (`Communities.community.importUsers.image.removeBackground`). Admin access grant/revoke via `grantUserAccess()`/`denyUserAccess()`.

### Blocked Users

`Communities::getBlockedUsersStream()` returns or creates a `Communities/user/blocked` stream (type `Streams/resource`) where blocked user relations are tracked. The `Communities/usersBlock/post` handler manages blocking/unblocking.

### Discourse SSO

The `Communities/discourseSSO` handler implements Discourse's SSO protocol for single-sign-on with external forums.

### NFT Integration

Column views for NFT display, collections, owned tokens, and NFT-linked profiles. Routes at `Communities/NFT/{publisherId}/{streamId}`, `Communities/NFTprofile/{userId}`, etc.

### Content Pages

Welcome page with configurable Ken Burns gallery, about page (`Streams/community/about` article stream), terms of service (with configurable jurisdiction), privacy policy, contribute/donate pages, adverts, feature board.

## User Streams

| Stream Name | Purpose |
|---|---|
| `Streams/chats/main` | Community conversations category |
| `Communities/create/permissions` | Permission to create communities |
| `Communities/user/blocked` | Blocked users list |
| `Streams/user/jobs` | User's job history |
| `Streams/user/education` | User's education history |
| `Streams/user/{platform}` | Social usernames (twitter, linkedin, github, facebook, instagram) |
| `Streams/community/about` | Community about page article |

## Roles

Communities adds no new roles but configures the standard hierarchy extensively: `Users/owners` and `Users/admins` can manage all aspects. Various config keys control which roles can create events (`Communities.newEvent.authorized`), import users (`Communities.users.canImport`), manage articles (`Communities.articles.canManage`), manage occupants (`Communities.occupants.canManage`), relate locations (`Communities.locations.canRelate`), and invite members (`Communities.community.canInvite`).

## Database

No custom tables. Uses Streams infrastructure, Users contacts/labels, and Places locations.

## Configuration

```json
{
    "Communities": {
        "tabs": {
            "community": {}, "people": {}, "events": {},
            "media": {}, "discuss": {}, "me": {}
        },
        "community": {
            "admins": ["Users/owners", "Users/admins"],
            "canInvite": ["Users/owners", "Users/admins"],
            "tabs": { "people": true, "events": true, "locations": true },
            "hideUntilParticipants": 10
        },
        "me": {
            "tabs": { "inbox": {"default": true}, "schedule": true, "profile": true,
                      "interests": true, "location": true, "credits": true, "gallery": true }
        },
        "profile": {
            "ordering": ["roles", "personal", "greeting", "social", "links", "jobs", "education"],
            "social": { "facebook": "facebook.com/", "twitter": "twitter.com/", "linkedin": "linkedin.com/in/" },
            "sections": { "personal": true, "greeting": true, "roles": true, "social": true },
            "tabs": { "about": false, "interests": true, "chat": true, "gallery": true }
        },
        "conversations": { "relationTypes": ["Streams/chat", "Websites/webpage"] },
        "pageSizes": { "events": 50, "people": 50, "conversations": 50 }
    }
}
```