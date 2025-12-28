# فاز 4: Frontend Development - راهنمای اجرا

## 📋 **اطلاعات کلی فاز**

- **مدت زمان**: 4 ماه (16 هفته)
- **بودجه**: $480,000
- **اولویت**: High
- **هدف**: توسعه کامل رابط کاربری

---

## 👥 **تیم مورد نیاز**

| نقش | تعداد | مسئولیت اصلی |
|-----|-------|---------------|
| Frontend Developer (React/Next.js) | 2 | Web Application Development |
| Mobile Developer (React Native) | 2 | Mobile App Development |
| UI/UX Designer | 1 | Design System، User Experience |
| QA Engineer | 1 | Testing، Quality Assurance |

### هزینه تیم:
- Frontend Developers: $10K/month × 2 = $20K/month
- Mobile Developers: $12K/month × 2 = $24K/month
- UI/UX Designer: $8K/month × 1 = $8K/month
- QA Engineer: $8K/month × 1 = $8K/month
- **مجموع ماهانه**: $60K × 4 ماه = $240K

---

## 🎯 **اهداف فاز**

### اهداف اصلی:
1. **Admin Panel (Filament PHP)** - کامل
2. **Web Application (Next.js)** - PWA
3. **Mobile App (React Native)** - Cross-platform
4. **Design System** - یکپارچه
5. **Real-time Features** - WebSocket integration

### معیارهای موفقیت:
- ✅ Admin panel fully functional
- ✅ Web app responsive (mobile-first)
- ✅ Mobile app published (iOS/Android)
- ✅ User acceptance > 85%
- ✅ Performance score > 90 (Lighthouse)

---

## 📅 **برنامه زمانی تفصیلی**

### ماه 1: Admin Panel و Design System

#### هفته 1-2: Admin Panel (Filament)
```yaml
Week 1:
  Days 1-3: Filament Setup و Configuration
    - Laravel Filament installation
    - Admin user management
    - Role-based access control
    - Basic dashboard setup
  
  Days 4-5: User Management Panel
    - User CRUD operations
    - User profile management
    - Account status management
    - Bulk operations

Week 2:
  Days 1-3: Content Management
    - Post moderation interface
    - Content filtering tools
    - Media management
    - Bulk content operations
  
  Days 4-5: Analytics Dashboard
    - Real-time metrics
    - User analytics
    - Content analytics
    - Performance monitoring
```

#### هفته 3-4: Design System و Component Library
```yaml
Week 3:
  Days 1-3: Design System Creation
    - Color palette definition
    - Typography system
    - Spacing and layout grid
    - Icon library
  
  Days 4-5: Component Library (Storybook)
    - Button components
    - Form components
    - Navigation components
    - Layout components

Week 4:
  Days 1-3: Advanced Components
    - Timeline components
    - Post components
    - User profile components
    - Modal and overlay components
  
  Days 4-5: Theme System
    - Light/dark theme
    - RTL support
    - Responsive breakpoints
    - Accessibility features
```

### ماه 2: Web Application Core Features

#### هفته 5-6: Next.js Setup و Authentication
```yaml
Week 5:
  Days 1-3: Next.js Project Setup
    - Project initialization
    - TypeScript configuration
    - ESLint and Prettier setup
    - Folder structure organization
  
  Days 4-5: Authentication System
    - Login/register pages
    - JWT token management
    - Protected routes
    - Social login integration

Week 6:
  Days 1-3: State Management
    - Redux Toolkit setup
    - API integration (RTK Query)
    - Global state management
    - Caching strategy
  
  Days 4-5: Routing و Navigation
    - Dynamic routing
    - Navigation components
    - Breadcrumb system
    - SEO optimization
```

#### هفته 7-8: Core Features Implementation
```yaml
Week 7:
  Days 1-3: Timeline و Feed
    - Timeline component
    - Infinite scrolling
    - Real-time updates
    - Post interactions
  
  Days 4-5: Post Creation
    - Rich text editor
    - Media upload
    - Draft functionality
    - Scheduled posts

Week 8:
  Days 1-3: User Profiles
    - Profile pages
    - Edit profile
    - Follow/unfollow
    - User statistics
  
  Days 4-5: Search و Discovery
    - Search interface
    - Trending topics
    - User suggestions
    - Hashtag pages
```

### ماه 3: Mobile App Development

#### هفته 9-10: React Native Setup
```yaml
Week 9:
  Days 1-3: React Native Project Setup
    - Expo/CLI setup
    - Navigation configuration
    - State management
    - API integration
  
  Days 4-5: Authentication Flow
    - Login/register screens
    - Biometric authentication
    - Token management
    - Onboarding flow

Week 10:
  Days 1-3: Core Navigation
    - Tab navigation
    - Stack navigation
    - Drawer navigation
    - Deep linking
  
  Days 4-5: Push Notifications
    - Firebase setup
    - Notification handling
    - Background notifications
    - Notification preferences
```

#### هفته 11-12: Mobile Features
```yaml
Week 11:
  Days 1-3: Timeline و Posts
    - Timeline screen
    - Post creation
    - Camera integration
    - Media picker
  
  Days 4-5: User Interactions
    - Profile screens
    - Follow system
    - Direct messaging
    - Notifications screen

Week 12:
  Days 1-3: Native Features
    - Camera functionality
    - Photo/video editing
    - Share functionality
    - Background sync
  
  Days 4-5: Performance Optimization
    - Image optimization
    - Lazy loading
    - Memory management
    - Bundle optimization
```

### ماه 4: Advanced Features و Testing

#### هفته 13-14: Advanced Web Features
```yaml
Week 13:
  Days 1-3: Real-time Features
    - WebSocket integration
    - Live notifications
    - Real-time messaging
    - Live timeline updates
  
  Days 4-5: PWA Implementation
    - Service worker
    - Offline functionality
    - App manifest
    - Install prompts

Week 14:
  Days 1-3: Advanced UI Features
    - Dark mode
    - Accessibility improvements
    - Keyboard shortcuts
    - Advanced search
  
  Days 4-5: Performance Optimization
    - Code splitting
    - Image optimization
    - Caching strategies
    - Bundle analysis
```

#### هفته 15-16: Testing و Deployment
```yaml
Week 15:
  Days 1-3: Comprehensive Testing
    - Unit tests (Jest)
    - Integration tests
    - E2E tests (Cypress)
    - Mobile testing (Detox)
  
  Days 4-5: User Acceptance Testing
    - Beta testing setup
    - Feedback collection
    - Bug fixes
    - Performance tuning

Week 16:
  Days 1-3: Deployment Preparation
    - Production builds
    - Environment configuration
    - CI/CD setup
    - App store preparation
  
  Days 4-5: Final Validation
    - Security testing
    - Performance validation
    - Accessibility audit
    - Documentation completion
```

---

## 🛠️ **تسکهای فنی تفصیلی**

### 1. Admin Panel (Filament PHP)

#### Dashboard Configuration:
```php
// app/Filament/Resources/DashboardResource.php
class DashboardResource extends Resource
{
    protected static ?string $model = null;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    public static function getWidgets(): array
    {
        return [
            UserStatsWidget::class,
            PostStatsWidget::class,
            EngagementWidget::class,
            RevenueWidget::class,
        ];
    }
}

// app/Filament/Widgets/UserStatsWidget.php
class UserStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->description('32k increase')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
                
            Stat::make('Active Users', User::where('last_seen_at', '>', now()->subDays(7))->count())
                ->description('7% increase')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
                
            Stat::make('New Users Today', User::whereDate('created_at', today())->count())
                ->description('3% decrease')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
        ];
    }
}
```

#### Content Moderation:
```php
// app/Filament/Resources/PostResource.php
class PostResource extends Resource
{
    protected static ?string $model = Post::class;
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('content')
                    ->required()
                    ->maxLength(280),
                    
                Select::make('status')
                    ->options([
                        'published' => 'Published',
                        'draft' => 'Draft',
                        'flagged' => 'Flagged',
                        'removed' => 'Removed',
                    ]),
                    
                Toggle::make('is_featured')
                    ->label('Featured Post'),
                    
                FileUpload::make('image')
                    ->image()
                    ->maxSize(5120),
            ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('content')
                    ->limit(50)
                    ->searchable(),
                    
                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'published',
                        'warning' => 'draft',
                        'danger' => 'flagged',
                    ]),
                    
                TextColumn::make('likes_count')
                    ->sortable(),
                    
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status'),
                Filter::make('flagged')
                    ->query(fn (Builder $query): Builder => $query->where('is_flagged', true)),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('approve')
                    ->action(fn (Post $record) => $record->update(['status' => 'published']))
                    ->requiresConfirmation(),
            ]);
    }
}
```

### 2. Next.js Web Application

#### Project Structure:
```typescript
// src/types/index.ts
export interface User {
  id: string;
  name: string;
  username: string;
  email: string;
  avatar?: string;
  bio?: string;
  followersCount: number;
  followingCount: number;
  postsCount: number;
  isFollowing?: boolean;
}

export interface Post {
  id: string;
  content: string;
  user: User;
  createdAt: string;
  updatedAt: string;
  likesCount: number;
  commentsCount: number;
  quotesCount: number;
  isLiked?: boolean;
  image?: string;
  video?: string;
  quotedPost?: Post;
  hashtags: Hashtag[];
}

export interface Timeline {
  posts: Post[];
  hasMore: boolean;
  nextCursor?: string;
}
```

#### API Integration:
```typescript
// src/services/api.ts
import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const api = createApi({
  reducerPath: 'api',
  baseQuery: fetchBaseQuery({
    baseUrl: process.env.NEXT_PUBLIC_API_URL,
    prepareHeaders: (headers, { getState }) => {
      const token = (getState() as RootState).auth.token;
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['Post', 'User', 'Timeline'],
  endpoints: (builder) => ({
    getTimeline: builder.query<Timeline, { cursor?: string }>({
      query: ({ cursor }) => ({
        url: '/timeline',
        params: cursor ? { cursor } : {},
      }),
      providesTags: ['Timeline'],
      serializeQueryArgs: ({ endpointName }) => endpointName,
      merge: (currentCache, newItems) => {
        currentCache.posts.push(...newItems.posts);
        currentCache.hasMore = newItems.hasMore;
        currentCache.nextCursor = newItems.nextCursor;
      },
      forceRefetch({ currentArg, previousArg }) {
        return currentArg?.cursor !== previousArg?.cursor;
      },
    }),
    
    createPost: builder.mutation<Post, { content: string; image?: File }>({
      query: (data) => {
        const formData = new FormData();
        formData.append('content', data.content);
        if (data.image) {
          formData.append('image', data.image);
        }
        
        return {
          url: '/posts',
          method: 'POST',
          body: formData,
        };
      },
      invalidatesTags: ['Timeline', 'Post'],
    }),
    
    likePost: builder.mutation<{ liked: boolean }, string>({
      query: (postId) => ({
        url: `/posts/${postId}/like`,
        method: 'POST',
      }),
      async onQueryStarted(postId, { dispatch, queryFulfilled }) {
        const patchResult = dispatch(
          api.util.updateQueryData('getTimeline', {}, (draft) => {
            const post = draft.posts.find(p => p.id === postId);
            if (post) {
              post.isLiked = !post.isLiked;
              post.likesCount += post.isLiked ? 1 : -1;
            }
          })
        );
        
        try {
          await queryFulfilled;
        } catch {
          patchResult.undo();
        }
      },
    }),
  }),
});

export const {
  useGetTimelineQuery,
  useCreatePostMutation,
  useLikePostMutation,
} = api;
```

#### Timeline Component:
```typescript
// src/components/Timeline/Timeline.tsx
import React, { useEffect, useRef } from 'react';
import { useGetTimelineQuery } from '@/services/api';
import { PostCard } from '@/components/Post/PostCard';
import { LoadingSpinner } from '@/components/UI/LoadingSpinner';

export const Timeline: React.FC = () => {
  const {
    data: timeline,
    isLoading,
    isFetching,
    fetchNextPage,
  } = useGetTimelineQuery({});
  
  const observerRef = useRef<IntersectionObserver>();
  const lastPostRef = useRef<HTMLDivElement>(null);
  
  useEffect(() => {
    if (isLoading || !timeline?.hasMore) return;
    
    if (observerRef.current) observerRef.current.disconnect();
    
    observerRef.current = new IntersectionObserver((entries) => {
      if (entries[0].isIntersecting && timeline.hasMore) {
        fetchNextPage();
      }
    });
    
    if (lastPostRef.current) {
      observerRef.current.observe(lastPostRef.current);
    }
    
    return () => observerRef.current?.disconnect();
  }, [isLoading, timeline?.hasMore, fetchNextPage]);
  
  if (isLoading) {
    return <LoadingSpinner />;
  }
  
  return (
    <div className="timeline">
      {timeline?.posts.map((post, index) => (
        <div
          key={post.id}
          ref={index === timeline.posts.length - 1 ? lastPostRef : null}
        >
          <PostCard post={post} />
        </div>
      ))}
      
      {isFetching && <LoadingSpinner />}
    </div>
  );
};
```

### 3. React Native Mobile App

#### Navigation Setup:
```typescript
// src/navigation/AppNavigator.tsx
import React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createStackNavigator } from '@react-navigation/stack';
import { useSelector } from 'react-redux';

import { AuthNavigator } from './AuthNavigator';
import { TimelineScreen } from '@/screens/TimelineScreen';
import { SearchScreen } from '@/screens/SearchScreen';
import { NotificationsScreen } from '@/screens/NotificationsScreen';
import { ProfileScreen } from '@/screens/ProfileScreen';

const Tab = createBottomTabNavigator();
const Stack = createStackNavigator();

const MainTabNavigator = () => (
  <Tab.Navigator
    screenOptions={({ route }) => ({
      tabBarIcon: ({ focused, color, size }) => {
        let iconName;
        
        switch (route.name) {
          case 'Timeline':
            iconName = focused ? 'home' : 'home-outline';
            break;
          case 'Search':
            iconName = focused ? 'search' : 'search-outline';
            break;
          case 'Notifications':
            iconName = focused ? 'notifications' : 'notifications-outline';
            break;
          case 'Profile':
            iconName = focused ? 'person' : 'person-outline';
            break;
        }
        
        return <Ionicons name={iconName} size={size} color={color} />;
      },
    })}
  >
    <Tab.Screen name="Timeline" component={TimelineScreen} />
    <Tab.Screen name="Search" component={SearchScreen} />
    <Tab.Screen name="Notifications" component={NotificationsScreen} />
    <Tab.Screen name="Profile" component={ProfileScreen} />
  </Tab.Navigator>
);

export const AppNavigator: React.FC = () => {
  const isAuthenticated = useSelector((state: RootState) => state.auth.isAuthenticated);
  
  return (
    <NavigationContainer>
      {isAuthenticated ? (
        <Stack.Navigator>
          <Stack.Screen 
            name="Main" 
            component={MainTabNavigator} 
            options={{ headerShown: false }}
          />
          <Stack.Screen name="PostDetail" component={PostDetailScreen} />
          <Stack.Screen name="UserProfile" component={UserProfileScreen} />
          <Stack.Screen name="CreatePost" component={CreatePostScreen} />
        </Stack.Navigator>
      ) : (
        <AuthNavigator />
      )}
    </NavigationContainer>
  );
};
```

#### Timeline Screen:
```typescript
// src/screens/TimelineScreen.tsx
import React, { useCallback } from 'react';
import {
  FlatList,
  RefreshControl,
  StyleSheet,
  View,
} from 'react-native';
import { useGetTimelineQuery } from '@/services/api';
import { PostCard } from '@/components/PostCard';
import { LoadingSpinner } from '@/components/LoadingSpinner';
import { CreatePostButton } from '@/components/CreatePostButton';

export const TimelineScreen: React.FC = () => {
  const {
    data: timeline,
    isLoading,
    isFetching,
    refetch,
    fetchNextPage,
  } = useGetTimelineQuery({});
  
  const handleRefresh = useCallback(() => {
    refetch();
  }, [refetch]);
  
  const handleLoadMore = useCallback(() => {
    if (timeline?.hasMore && !isFetching) {
      fetchNextPage();
    }
  }, [timeline?.hasMore, isFetching, fetchNextPage]);
  
  const renderPost = useCallback(({ item }) => (
    <PostCard post={item} />
  ), []);
  
  const renderFooter = useCallback(() => {
    if (!isFetching) return null;
    return <LoadingSpinner style={styles.footer} />;
  }, [isFetching]);
  
  if (isLoading) {
    return <LoadingSpinner style={styles.centered} />;
  }
  
  return (
    <View style={styles.container}>
      <FlatList
        data={timeline?.posts || []}
        renderItem={renderPost}
        keyExtractor={(item) => item.id}
        refreshControl={
          <RefreshControl
            refreshing={isLoading}
            onRefresh={handleRefresh}
          />
        }
        onEndReached={handleLoadMore}
        onEndReachedThreshold={0.5}
        ListFooterComponent={renderFooter}
        showsVerticalScrollIndicator={false}
      />
      
      <CreatePostButton />
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#fff',
  },
  centered: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  footer: {
    paddingVertical: 20,
  },
});
```

### 4. Real-time Features

#### WebSocket Integration:
```typescript
// src/services/websocket.ts
class WebSocketService {
  private ws: WebSocket | null = null;
  private reconnectAttempts = 0;
  private maxReconnectAttempts = 5;
  private reconnectInterval = 1000;
  
  connect(token: string) {
    const wsUrl = `${process.env.NEXT_PUBLIC_WS_URL}?token=${token}`;
    this.ws = new WebSocket(wsUrl);
    
    this.ws.onopen = () => {
      console.log('WebSocket connected');
      this.reconnectAttempts = 0;
    };
    
    this.ws.onmessage = (event) => {
      const data = JSON.parse(event.data);
      this.handleMessage(data);
    };
    
    this.ws.onclose = () => {
      console.log('WebSocket disconnected');
      this.reconnect(token);
    };
    
    this.ws.onerror = (error) => {
      console.error('WebSocket error:', error);
    };
  }
  
  private handleMessage(data: any) {
    switch (data.type) {
      case 'new_post':
        store.dispatch(api.util.invalidateTags(['Timeline']));
        break;
        
      case 'post_liked':
        store.dispatch(
          api.util.updateQueryData('getTimeline', {}, (draft) => {
            const post = draft.posts.find(p => p.id === data.postId);
            if (post) {
              post.likesCount = data.likesCount;
            }
          })
        );
        break;
        
      case 'notification':
        store.dispatch(addNotification(data.notification));
        break;
    }
  }
  
  private reconnect(token: string) {
    if (this.reconnectAttempts < this.maxReconnectAttempts) {
      setTimeout(() => {
        this.reconnectAttempts++;
        this.connect(token);
      }, this.reconnectInterval * Math.pow(2, this.reconnectAttempts));
    }
  }
  
  disconnect() {
    if (this.ws) {
      this.ws.close();
      this.ws = null;
    }
  }
}

export const websocketService = new WebSocketService();
```

---

## 📊 **ابزارها و تکنولوژیها**

### Frontend Technologies:
```yaml
Web (Next.js):
  - React 18
  - TypeScript
  - Tailwind CSS
  - Redux Toolkit
  - React Hook Form
  - Framer Motion

Mobile (React Native):
  - React Native 0.72+
  - TypeScript
  - React Navigation 6
  - Redux Toolkit
  - React Native Reanimated
  - React Native Gesture Handler
```

### Development Tools:
```yaml
Build Tools:
  - Vite/Webpack
  - Babel
  - ESLint
  - Prettier
  - Husky

Testing:
  - Jest
  - React Testing Library
  - Cypress
  - Detox (React Native)
  - Storybook
```

---

## 🔍 **تست و اعتبارسنجی**

### Testing Strategy:
```typescript
// __tests__/components/Timeline.test.tsx
import { render, screen, waitFor } from '@testing-library/react';
import { Provider } from 'react-redux';
import { Timeline } from '@/components/Timeline/Timeline';
import { store } from '@/store';

const renderWithProvider = (component: React.ReactElement) => {
  return render(
    <Provider store={store}>
      {component}
    </Provider>
  );
};

describe('Timeline Component', () => {
  it('renders timeline posts', async () => {
    renderWithProvider(<Timeline />);
    
    await waitFor(() => {
      expect(screen.getByTestId('timeline')).toBeInTheDocument();
    });
  });
  
  it('loads more posts on scroll', async () => {
    renderWithProvider(<Timeline />);
    
    // Simulate scroll to bottom
    fireEvent.scroll(window, { target: { scrollY: 1000 } });
    
    await waitFor(() => {
      expect(screen.getByTestId('loading-spinner')).toBeInTheDocument();
    });
  });
});
```

### E2E Testing:
```typescript
// cypress/e2e/timeline.cy.ts
describe('Timeline Flow', () => {
  beforeEach(() => {
    cy.login('test@example.com', 'password');
    cy.visit('/');
  });
  
  it('should display timeline posts', () => {
    cy.get('[data-testid="timeline"]').should('be.visible');
    cy.get('[data-testid="post-card"]').should('have.length.greaterThan', 0);
  });
  
  it('should create a new post', () => {
    cy.get('[data-testid="create-post-button"]').click();
    cy.get('[data-testid="post-content"]').type('This is a test post');
    cy.get('[data-testid="submit-post"]').click();
    
    cy.get('[data-testid="timeline"]')
      .should('contain', 'This is a test post');
  });
  
  it('should like a post', () => {
    cy.get('[data-testid="like-button"]').first().click();
    cy.get('[data-testid="like-count"]').should('contain', '1');
  });
});
```

---

## 📈 **نظارت و گزارشگیری**

### Performance Metrics:
```yaml
Web Performance:
  - Lighthouse score > 90
  - First Contentful Paint < 1.5s
  - Largest Contentful Paint < 2.5s
  - Cumulative Layout Shift < 0.1
  - Time to Interactive < 3s

Mobile Performance:
  - App startup time < 2s
  - Screen transition time < 300ms
  - Memory usage < 100MB
  - Battery usage optimization
```

### User Experience Metrics:
```yaml
Engagement:
  - Session duration
  - Page views per session
  - Bounce rate
  - User retention rate
  - Feature adoption rate

Conversion:
  - Registration completion rate
  - Post creation rate
  - Social interaction rate
  - App store ratings
```

---

## ✅ **Deliverables**

### Month 4 Deliverables:
1. **Admin Panel**
   - Complete Filament-based admin interface
   - User and content management
   - Analytics dashboard
   - System monitoring tools

2. **Web Application**
   - Responsive Next.js application
   - PWA capabilities
   - Real-time features
   - SEO optimization

3. **Mobile Application**
   - Cross-platform React Native app
   - Native features integration
   - Push notifications
   - App store ready builds

4. **Design System**
   - Component library
   - Design tokens
   - Storybook documentation
   - Accessibility guidelines

---

## 🚨 **ریسکها و کاهش آنها**

### High Risk:
```yaml
Risk: Cross-platform Compatibility Issues
Mitigation: Extensive testing on multiple devices, progressive enhancement

Risk: Performance Issues on Low-end Devices
Mitigation: Performance budgets, optimization techniques, graceful degradation

Risk: App Store Approval Delays
Mitigation: Early submission, compliance with guidelines, beta testing
```

### Medium Risk:
```yaml
Risk: Real-time Feature Complexity
Mitigation: Fallback mechanisms, progressive enhancement, thorough testing

Risk: Design System Inconsistencies
Mitigation: Automated design token validation, component audits
```

---

*این سند راهنمای کامل اجرای فاز 4 است و باید بهروزرسانی منظم شود.*