# Auth Package - Laravilt Forms Refactoring

## ✅ Completed Refactoring

### Backend Changes (PHP)

All auth pages have been refactored to use **Laravilt Forms Components** instead of manual Inertia props:

#### 1. **Login.php**
- ✅ Now uses `TextInput` for email and password fields
- ✅ Uses `Checkbox` for "remember me"
- ✅ Defines `getSchema()` method returning form components
- ✅ Renders to `laravilt/AuthPage` component (universal auth page)
- ✅ Passes form schema as serialized props via `toLaraviltProps()`

**Form Components:**
```php
TextInput::make('email')->label('Email')->email()->required()->autofocus()
TextInput::make('password')->label('Password')->password()->required()
Checkbox::make('remember')->label('Remember me')
```

#### 2. **OTP.php**
- ✅ Now uses `PinInput` component (6-digit numeric OTP)
- ✅ Defines `getSchema()` method
- ✅ Renders to `laravilt/AuthPage`
- ✅ Uses `.otp(true)` and `.type('numeric')` for proper OTP behavior

**Form Components:**
```php
PinInput::make('code')
    ->label('Verification Code')
    ->length(6)
    ->otp(true)
    ->type('numeric')
    ->required()
    ->autofocus()
```

#### 3. **Register.php**
- ✅ Uses `TextInput` for name, email, password, and password confirmation
- ✅ Defines `getSchema()` method
- ✅ Renders to `laravilt/AuthPage`

**Form Components:**
```php
TextInput::make('name')->label('Name')->required()->maxLength(255)
TextInput::make('email')->label('Email')->email()->required()
TextInput::make('password')->label('Password')->password()->required()
TextInput::make('password_confirmation')->label('Confirm Password')->password()->required()
```

#### 4. **PasswordReset.php**
- ✅ Has **two schemas**: `getForgotSchema()` and `getResetSchema()`
- ✅ Uses `TextInput` for email and password fields
- ✅ Renders to `laravilt/AuthPage`
- ✅ Passes `hiddenFields` (token, email) for reset form

**Form Components:**
```php
// Forgot Password
TextInput::make('email')->label('Email')->email()->required()

// Reset Password
TextInput::make('password')->label('New Password')->password()->required()
TextInput::make('password_confirmation')->label('Confirm Password')->password()->required()
```

#### 5. **Profile.php**
- ✅ Has **three schemas**: `getProfileSchema()`, `getPasswordSchema()`, `getDeleteSchema()`
- ✅ Uses `TextInput` for all fields
- ✅ Renders to `laravilt/ProfilePage` (dedicated profile component)
- ✅ Passes user data with `.default()` for pre-filling

**Form Components:**
```php
// Profile Information
TextInput::make('name')->default($user->name)
TextInput::make('email')->default($user->email)

// Update Password
TextInput::make('current_password')->password()->required()
TextInput::make('password')->password()->required()
TextInput::make('password_confirmation')->password()->required()

// Delete Account
TextInput::make('password')->password()->required()
```

### Architecture Changes

#### **Before (Manual Props)**
```php
return Inertia::render('user/auth/Login', [
    'canResetPassword' => true,
    'canRegister' => true,
    'status' => session('status'),
]);
```

**Problems:**
- ❌ Each page had its own custom Vue component
- ❌ Manual input field creation in Vue
- ❌ No form schema/structure
- ❌ Inconsistent styling across auth pages

#### **After (Laravilt Forms)**
```php
protected function getSchema(): array
{
    return [
        TextInput::make('email')->email()->required(),
        TextInput::make('password')->password()->required(),
    ];
}

public function create()
{
    return Inertia::render('laravilt/AuthPage', [
        'page' => [
            'heading' => static::getTitle(),
            'schema' => collect($this->getSchema())->map->toLaraviltProps()->toArray(),
        ],
        'formAction' => $this->getPanel()->url('login'),
        'formMethod' => 'POST',
    ]);
}
```

**Benefits:**
- ✅ Universal `laravilt/AuthPage` component for all simple auth pages
- ✅ Form fields defined in backend PHP using fluent API
- ✅ Automatic form rendering from schema
- ✅ Consistent styling across all auth pages
- ✅ Type safety with Laravilt form components
- ✅ Easy to add/remove/modify fields

### View Path Strategy

#### **Panel Pages** (Dashboard, Resources, etc.)
- Render to Vue/Inertia components: `'user/Dashboard'`, `'admin/Reports'`
- Use `PanelLayout` for authenticated panel pages
- Generated via `php artisan laravilt:page`

#### **Auth Pages** (Login, Register, OTP, etc.)
- Render to universal component: `'laravilt/AuthPage'`
- Use simple auth layout (card-based)
- Form schema defined in backend

#### **Frontend Pages** (Welcome, Marketing, etc.)
- Render to Blade views: `'welcome'`, `'about'`
- Not panel-specific
- Can use Blade form components

### Component Hierarchy

```
Auth Pages (Backend)
  ├─ Login.php → getSchema() → laravilt/AuthPage
  ├─ Register.php → getSchema() → laravilt/AuthPage
  ├─ OTP.php → getSchema() → laravilt/AuthPage
  ├─ PasswordReset.php → getSchema() → laravilt/AuthPage
  └─ Profile.php → getSchema() → laravilt/ProfilePage

Vue Components (Frontend - To Be Created)
  ├─ laravilt/AuthPage.vue
  │   ├─ Renders form schema dynamically
  │   ├─ Uses Laravilt form components
  │   └─ Simple auth card layout
  └─ laravilt/ProfilePage.vue
      ├─ Three separate forms (profile, password, delete)
      └─ Uses panel layout
```

## 📋 Next Steps

### 1. Create Vue Components

Need to create two universal Vue components:

#### `resources/js/Pages/laravilt/AuthPage.vue`
- Renders form schema dynamically
- Uses Laravilt `FormRenderer` component
- Simple card layout for auth (no panel chrome)
- Displays heading, subheading, status messages
- Shows links (e.g., "Forgot Password?", "Register")

#### `resources/js/Pages/laravilt/ProfilePage.vue`
- Uses `PanelLayout` (authenticated panel page)
- Three separate card sections:
  - Profile Information
  - Update Password
  - Delete Account
- Each section renders its own form schema
- Shows status messages for each section

### 2. Form Rendering

Both components should use Laravilt's form rendering:

```vue
<script setup lang="ts">
import { FormRenderer } from '@laravilt/forms'

const props = defineProps<{
    page: {
        heading: string
        subheading?: string
        schema: any[]
    }
    formAction: string
    formMethod: string
}>()
</script>

<template>
    <AuthCardLayout>
        <h1>{{ page.heading }}</h1>
        <p v-if="page.subheading">{{ page.subheading }}</p>

        <FormRenderer
            :schema="page.schema"
            :action="formAction"
            :method="formMethod"
        />
    </AuthCardLayout>
</template>
```

### 3. Test All Auth Flows

After creating Vue components, test:
- ✅ Login with Laravilt TextInput
- ✅ Register with Laravilt TextInput
- ✅ OTP with Laravilt PinInput (6 digits)
- ✅ Password reset with Laravilt TextInput
- ✅ Profile update with Laravilt forms

## 🎯 Benefits of This Refactoring

### 1. **Consistency**
- All auth pages use the same Laravilt form components
- Uniform styling and behavior
- Same validation patterns

### 2. **Maintainability**
- Form schema defined in one place (backend)
- Easy to modify fields (just change `getSchema()`)
- No need to update Vue components for field changes

### 3. **Type Safety**
- Laravilt form components are strongly typed
- IDE autocompletion for all form methods
- Compile-time checking

### 4. **Developer Experience**
- Fluent API for building forms: `.email()`, `.required()`, `.password()`
- Less boilerplate code
- Faster development

### 5. **Extensibility**
- Easy to add new form components
- Can customize rendering per field
- Support for complex fields (file uploads, rich text, etc.)

### 6. **Architecture Alignment**
- Matches panel page generation pattern
- Auth pages extend `Page` class like panel pages
- Consistent with Laravilt's philosophy

## 📊 Comparison

| Aspect | Before | After |
|--------|--------|-------|
| **Form Definition** | Vue templates | PHP schema |
| **Components** | 5 separate Vue pages | 2 universal Vue pages |
| **Styling** | Manual per page | Automatic from schema |
| **Type Safety** | Limited | Full TypeScript + PHP |
| **Maintainability** | Medium | High |
| **Consistency** | Variable | Uniform |
| **DX** | Manual forms | Fluent API |

## 🔧 Technical Details

### Form Schema Serialization

Each field component has `toLaraviltProps()` method that serializes to array:

```php
TextInput::make('email')
    ->label('Email')
    ->email()
    ->required()
    ->toLaraviltProps()
```

Becomes:

```php
[
    'name' => 'email',
    'type' => 'email',
    'label' => 'Email',
    'required' => true,
    'component' => 'TextInput',
    // ...other props
]
```

### Vue Component Rendering

The `FormRenderer` component receives the schema and renders each field:

```vue
<FormRenderer :schema="schema" />
```

Renders:

```vue
<TextInput
    name="email"
    type="email"
    label="Email"
    :required="true"
/>
```

---

**Status**: Backend refactoring complete ✅
**Next**: Create Vue components for rendering
**Version**: 2.0 (Laravilt Forms)
