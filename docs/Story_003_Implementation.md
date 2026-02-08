# Story 003: Face Enrollment Implementation

## Overview

Complete implementation of face enrollment system for staff attendance using AWS Rekognition. This allows administrators to register multiple face photos for each staff member before they can use the facial recognition-based attendance system.

## Implementation Date

January 2025

## Technical Stack

-   **Framework**: Laravel 12 + Livewire Volt
-   **Face Recognition**: AWS Rekognition (IndexFaces API)
-   **Image Processing**: Native PHP (with OpenCV placeholder for future enhancement)
-   **Storage**: Local filesystem (storage/app/faces/raw and faces/cropped)
-   **Database**: MySQL (face_profiles table)

## Database Schema

### Migration: `2025_10_30_093631_create_face_profiles_table.php`

```php
Schema::create('face_profiles', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('face_id')->unique(); // AWS Rekognition Face ID
    $table->string('provider')->default('aws_rekognition');
    $table->string('collection_id')->nullable();
    $table->string('image_path');
    $table->decimal('confidence', 5, 2)->nullable(); // 0.00 - 100.00
    $table->timestamps();

    $table->index('user_id');
    $table->index('face_id');
});
```

## Models

### FaceProfile Model

**File**: `app/Models/FaceProfile.php`

**Key Features**:

-   Relationship to User model (belongsTo)
-   Stores AWS Rekognition metadata (face_id, collection_id, confidence)
-   Tracks image path for retrieval and display
-   Cascading delete on user removal

**Attributes**:

```php
protected $fillable = [
    'user_id',
    'face_id',
    'provider',
    'collection_id',
    'image_path',
    'confidence',
];

protected $casts = [
    'confidence' => 'decimal:2',
];
```

### User Model Extension

**File**: `app/Models/User.php`

**New Methods**:

```php
// Check if user has at least one face registered
public function hasFaceRegistered(): bool
{
    return $this->faceProfiles()->exists();
}

// Get count of registered faces
public function faceCount(): int
{
    return $this->faceProfiles()->count();
}

// Relationship to face profiles
public function faceProfiles()
{
    return $this->hasMany(FaceProfile::class);
}
```

## Services

### 1. FaceProcessor Service

**File**: `app/Services/FaceProcessor.php`

**Purpose**: Pre-process face images before AWS enrollment

**Current Implementation**:

-   Validates image format (jpeg, jpg, png)
-   Checks minimum dimensions (200x200)
-   Saves raw image
-   Creates "cropped" version (currently just a copy - **TODO: OpenCV integration**)
-   Returns paths for both versions

**Key Method**:

```php
public function processImage(UploadedFile $image, int $userId): array
{
    // Validates format and dimensions
    // Saves to storage/app/faces/raw/{userId}/
    // Creates cropped version in storage/app/faces/cropped/{userId}/
    // Returns ['raw_path' => ..., 'cropped_path' => ...]
}
```

**Future Enhancement**:

-   Integrate OpenCV for actual face detection and cropping
-   Add face quality assessment
-   Detect multiple faces and warn user
-   Auto-rotate based on EXIF data

### 2. FaceEnrollService Service

**File**: `app/Services/FaceEnrollService.php`

**Purpose**: Interact with AWS Rekognition for face enrollment and management

**Dependencies**:

-   AWS SDK for PHP v3
-   Environment variables: AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_DEFAULT_REGION, REKOG_COLLECTION

**Key Methods**:

#### enrollFace()

```php
public function enrollFace(int $userId, string $imagePath): FaceProfile
```

-   Ensures AWS collection exists
-   Calls IndexFaces API to register face
-   Saves face metadata to database
-   Returns FaceProfile model
-   Throws exception if no face detected or on AWS error

#### deleteFace()

```php
public function deleteFace(FaceProfile $faceProfile): void
```

-   Removes face from AWS Rekognition collection
-   Deletes FaceProfile record from database
-   Deletes associated image file from storage

#### ensureCollectionExists()

```php
private function ensureCollectionExists(): void
```

-   Checks if collection exists in AWS
-   Creates collection if not found
-   Uses REKOG_COLLECTION from .env

**AWS Rekognition Configuration**:

```env
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=ap-southeast-2
REKOG_COLLECTION=staf_desa_teromu
```

## Livewire Components

### 1. StaffList Component

**File**: `app/Livewire/Admin/Faces/StaffList.php`
**View**: `resources/views/livewire/admin/faces/staff-list.blade.php`
**Route**: `/admin/faces`

**Purpose**: Display all staff members with face enrollment status

**Features**:

-   Live search by name or email
-   Pagination (10 per page)
-   Display face count for each staff
-   Visual status indicator (enrolled/not enrolled)
-   "Kelola Wajah" link to manage faces

**Key Properties**:

```php
#[Url(as: 'q')]
public string $search = '';
```

**Query**:

```php
User::where('role', 'staff')
    ->where(function ($query) {
        $query->where('name', 'like', "%{$this->search}%")
              ->orWhere('email', 'like', "%{$this->search}%");
    })
    ->withCount('faceProfiles')
    ->paginate(10)
```

**UI Elements**:

-   Search input with live filtering
-   Table with columns: Name, Email, Status, Faces Count, Action
-   Status badge: Green (Terdaftar) / Red (Belum Terdaftar)
-   Dark mode support throughout

### 2. Manage Component

**File**: `app/Livewire/Admin/Faces/Manage.php`
**View**: `resources/views/livewire/admin/faces/manage.blade.php`
**Route**: `/admin/faces/{userId}`

**Purpose**: Upload and manage face photos for individual staff

**Features**:

-   Upload face photo with validation
-   Display all registered faces for the staff
-   Delete individual face profiles
-   Image preview before upload
-   Success/error notifications

**Key Properties**:

```php
public $userId;
public $user;
public $photo;
```

**Validation Rules**:

```php
$this->validate([
    'photo' => 'required|image|max:2048|mimes:jpeg,jpg,png',
]);
```

**Key Methods**:

#### uploadFace()

1. Validates uploaded image
2. Processes image via FaceProcessor (detect & crop)
3. Enrolls to AWS Rekognition via FaceEnrollService
4. Saves metadata to face_profiles table
5. Shows success notification
6. Reloads user data
7. Resets photo property

#### deleteFace()

1. Confirms deletion with wire:confirm
2. Calls FaceEnrollService to remove from AWS
3. Deletes from database
4. Deletes image file
5. Shows success notification
6. Reloads user data

**UI Elements**:

-   Two-column layout (responsive)
-   Left column: Upload form with preview, guidelines, submit button
-   Right column: Grid of registered faces with Face ID, confidence, thumbnail, delete button
-   Guidelines display: minimum resolution, lighting tips, supported formats
-   Dark mode support

## Routes

### Admin Routes (Protected by `auth` and `role:admin` middleware)

```php
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/faces', \App\Livewire\Admin\Faces\StaffList::class)->name('faces');
    Route::get('/faces/{userId}', \App\Livewire\Admin\Faces\Manage::class)->name('faces.manage');
});
```

## Navigation

### Sidebar Menu (Admin Only)

**File**: `resources/views/components/layouts/app/sidebar.blade.php`

Added menu item:

```blade
<flux:navlist.item icon="user-group" :href="route('admin.faces')"
    :current="request()->routeIs('admin.faces*')" wire:navigate>{{ __('Faces') }}
</flux:navlist.item>
```

## User Flow

### Admin Flow: Register Staff Face

1. Admin logs in and navigates to "Faces" menu
2. Views list of all staff with enrollment status
3. Searches for specific staff (optional)
4. Clicks "Kelola Wajah" for target staff
5. Uploads face photo (jpeg/jpg/png, max 2MB)
6. Sees preview of uploaded image
7. Clicks "Upload Wajah" button
8. System processes image:
    - Validates format and size
    - Detects face (currently basic validation, TODO: OpenCV)
    - Crops face region (currently copies, TODO: OpenCV)
    - Enrolls to AWS Rekognition
    - Saves metadata to database
9. Success notification appears
10. New face appears in registered faces list
11. Can upload additional faces (multiple photos per staff supported)
12. Can delete individual faces if needed

### System Flow: Face Enrollment Process

```
Upload → Validate → Process (FaceProcessor) → Enroll (AWS Rekognition) → Save (Database) → Done
```

**Detailed Steps**:

1. **Upload**: User selects image file
2. **Validate**: Check format (jpeg/jpg/png), size (max 2MB)
3. **Process**:
    - Save raw image to storage/app/faces/raw/{userId}/
    - Validate dimensions (min 200x200)
    - Create cropped version in storage/app/faces/cropped/{userId}/
4. **Enroll**:
    - Ensure AWS collection exists
    - Call IndexFaces API with cropped image
    - Receive face_id and confidence score
5. **Save**:
    - Create FaceProfile record with user_id, face_id, confidence, image_path
    - Link to User model
6. **Done**: Show success notification, reload face list

## Error Handling

### FaceProcessor Errors

-   Invalid image format → "Invalid image format. Supported: jpeg, jpg, png"
-   Dimensions too small → "Image dimensions must be at least 200x200 pixels"
-   Storage failure → "Failed to save image"

### FaceEnrollService Errors

-   No face detected → "No face detected in the image"
-   AWS API error → "Failed to enroll face: {error_message}"
-   Collection creation error → "Failed to create Rekognition collection"

### Validation Errors

-   Missing photo → "The photo field is required"
-   File too large → "The photo must not be greater than 2048 kilobytes"
-   Wrong format → "The photo must be a file of type: jpeg, jpg, png"

## Security Considerations

1. **Authentication**: All routes protected by `auth` middleware
2. **Authorization**: Only `role:admin` can access face management
3. **File Upload**: Validated format and size to prevent malicious uploads
4. **AWS Credentials**: Stored in .env, never exposed to client
5. **Cascade Delete**: Face profiles automatically deleted when user is removed
6. **File Storage**: Images stored in private storage directory (storage/app/faces)

## Performance Considerations

1. **Pagination**: Staff list paginated (10 per page) to handle large datasets
2. **Lazy Loading**: Face profiles loaded only when viewing specific staff
3. **Image Size**: Max 2MB to balance quality and upload speed
4. **AWS API**: Single IndexFaces call per upload (not batched)
5. **Database Indexes**: Added on user_id and face_id for faster queries

## Testing Checklist

-   [x] Database migration runs successfully
-   [x] Models have correct relationships
-   [x] FaceProcessor validates image format
-   [x] FaceProcessor checks minimum dimensions
-   [x] FaceProcessor saves raw and cropped images
-   [ ] FaceEnrollService creates AWS collection if not exists
-   [ ] FaceEnrollService enrolls face to Rekognition
-   [ ] FaceEnrollService saves metadata to database
-   [ ] StaffList displays all staff correctly
-   [ ] StaffList search filters by name/email
-   [ ] StaffList shows accurate face count
-   [ ] Manage component loads staff data
-   [ ] Manage component validates file upload
-   [ ] Manage component processes and enrolls face
-   [ ] Manage component displays registered faces
-   [ ] Manage component deletes face from AWS and database
-   [ ] Dark mode works correctly on all screens
-   [ ] Notifications appear for success/error
-   [ ] Routes are protected by auth and role middleware
-   [ ] Sidebar menu shows Faces for admin only

## Acceptance Criteria Status

From Story_003.md:

1. ✅ **Database**: face_profiles table with user_id, face_id, provider, collection_id, image_path, confidence
2. ✅ **Admin UI**: Face enrollment page accessible from admin menu
3. ✅ **Upload**: Admin can upload face photo for staff (multiple photos supported)
4. ✅ **Processing**: Image processed (validated, saved raw and cropped)
5. 🔄 **AWS Integration**: Face enrolled to Rekognition (needs AWS credentials to test)
6. ✅ **Display**: Admin can view all registered faces for a staff
7. ✅ **Delete**: Admin can delete individual face profiles

**Legend**:

-   ✅ Implemented and code-complete
-   🔄 Implemented but requires AWS credentials to fully test
-   ❌ Not implemented

## Known Limitations

1. **OpenCV Integration**: Currently using dummy face detection
    - FaceProcessor copies image instead of detecting and cropping face
    - Future enhancement: Integrate OpenCV for real face detection
2. **Face Quality Assessment**: No quality check before enrollment
    - Should verify lighting, resolution, face angle
    - AWS may reject poor quality images
3. **Multiple Faces**: No detection of multiple faces in one image
    - User should be warned if multiple faces detected
4. **AWS Error Details**: Generic error messages for AWS failures

    - Could provide more specific guidance (e.g., "Face too dark", "Face too blurry")

5. **Image Optimization**: No automatic resizing or compression
    - Large images may slow down upload and processing

## Future Enhancements

1. **OpenCV Integration**:
    - Real face detection and cropping
    - Face quality assessment
    - Multiple face detection warning
2. **Advanced Validation**:
    - Check for duplicate faces before enrollment
    - Verify face quality score from AWS
    - Detect and reject obscured faces (sunglasses, mask)
3. **Batch Operations**:
    - Upload multiple faces at once
    - Bulk delete faces
    - Export face data
4. **Analytics**:
    - Track enrollment success rate
    - Monitor AWS API usage
    - Display confidence score trends
5. **User Experience**:
    - Drag-and-drop upload
    - Webcam capture for live enrollment
    - Progress indicator for AWS processing
    - Preview enrolled faces before finalizing

## Dependencies

### PHP Packages

```json
{
    "aws/aws-sdk-php": "^3.357"
}
```

### Environment Variables

```env
AWS_ACCESS_KEY_ID=your_access_key_here
AWS_SECRET_ACCESS_KEY=your_secret_key_here
AWS_DEFAULT_REGION=ap-southeast-2
REKOG_COLLECTION=staf_desa_teromu
```

### Storage Directories

```
storage/app/
  └── faces/
      ├── raw/
      │   └── {user_id}/
      │       └── {timestamp}_{filename}
      └── cropped/
          └── {user_id}/
              └── {timestamp}_{filename}
```

## Troubleshooting

### Issue: "No face detected in the image"

**Cause**: AWS Rekognition couldn't find a face in the uploaded image
**Solution**:

-   Ensure face is clearly visible and well-lit
-   Face should be front-facing
-   Remove sunglasses, masks, or obstructions
-   Upload a higher quality image

### Issue: "Failed to create Rekognition collection"

**Cause**: AWS credentials invalid or insufficient permissions
**Solution**:

-   Verify AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY in .env
-   Check IAM user has rekognition:CreateCollection permission
-   Verify AWS_DEFAULT_REGION is correct

### Issue: Image upload fails silently

**Cause**: Storage directory not writable
**Solution**:

```bash
# On Windows with Laragon
icacls storage /grant Everyone:F /T

# Or create directories manually
mkdir storage\app\faces\raw
mkdir storage\app\faces\cropped
```

### Issue: Face enrollment succeeds but image not displayed

**Cause**: Image path stored incorrectly or storage link missing
**Solution**:

-   Check image_path in face_profiles table
-   Verify file exists in storage/app/faces/cropped/{user_id}/
-   Run `php artisan storage:link` if needed (though we're using app/faces, not public)

## Related Documentation

-   [Story_003.md](Story_003.md) - Original user story and requirements
-   [Story_001.md](Story_001.md) - User authentication and roles
-   [Story_002_Implementation.md](Story_002_Implementation.md) - Geofencing implementation
-   [Middleware_Strategy.md](Middleware_Strategy.md) - Role-based access control

## Conclusion

Story_003 has been successfully implemented with a complete face enrollment system using AWS Rekognition. The implementation provides:

-   ✅ Secure admin-only access to face management
-   ✅ User-friendly upload interface with validation
-   ✅ AWS Rekognition integration for face indexing
-   ✅ Database tracking of enrolled faces
-   ✅ Dark mode support throughout
-   ✅ Error handling and user notifications

**Next Steps**:

1. Configure AWS credentials in .env
2. Test face enrollment with real images
3. Implement Story_004: Face Recognition for Attendance
4. Integrate OpenCV for better face detection (future enhancement)
