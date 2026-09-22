# Upload Images to Production Server - Step by Step Guide

## Current Status
✅ **Local files check**: All 7 image files referenced in your local database exist  
❌ **Production issue**: Images not uploaded to production server yet

## Files to Upload

You need to upload these **8 image files** from your local machine to your production server:

1. `inventory_1766794960_694f26d0981a6.jpg`
2. `inventory_1766794992_694f26f023066.jpg`
3. `inventory_1766795596_694f294cc22d3.jpg`
4. `inventory_1766799570_694f38d215170.jpg`
5. `inventory_1766804368_694f4b9089669.jpg`
6. `itemlist_1766804344_694f4b7808192.jpg`
7. `itemlist_1766808023_694f59d7122b8.jpg`
8. `itemlist_1766808103_694f5a27b265c.jpg`

**Location on your local machine:**
```
C:\xampp\htdocs\POS_medSupplies\public\images\inventory\
```

## Step 1: Connect to Your Hostinger Server

### Option A: Using FileZilla (Recommended)

1. **Download FileZilla** (if you don't have it): https://filezilla-project.org/

2. **Connect to your server:**
   - Open FileZilla
   - Host: `ftp.redempmedsupplies.com` (or your FTP hostname)
   - Username: Your FTP username
   - Password: Your FTP password
   - Port: 21 (or 22 for SFTP)
   - Click "Quickconnect"

3. **Navigate to the correct directory:**
   - On the **right side** (Remote site), navigate to: `public_html/public/images/`
   - **Important**: The database paths are stored as `images/inventory/filename.jpg`, so files must be in `public/images/inventory/`
   - If that doesn't exist, try: `public_html/images/` (depending on your Laravel setup)
   - If `inventory` folder doesn't exist, **create it** (right-click → Create directory → name it "inventory")

4. **Navigate on the left side** (Local site):
   - Go to: `C:\xampp\htdocs\POS_medSupplies\public\images\inventory\`

5. **Upload files:**
   - Select all 8 .jpg files on the left
   - Drag them to the `inventory` folder on the right
   - Wait for upload to complete

6. **Set permissions:**
   - Right-click on the `inventory` folder → File permissions
   - Set to: `755` (or `0755`)
   - Select all uploaded image files → Right-click → File permissions
   - Set to: `644` (or `0644`)

### Option B: Using Hostinger File Manager

1. **Login to Hostinger control panel**
2. **Go to File Manager**
3. **Navigate to**: `public_html/public/images/` (or `public_html/images/`)
4. **Create folder** `inventory` if it doesn't exist
5. **Upload files**:
   - Click "Upload" button
   - Select all 8 .jpg files from `C:\xampp\htdocs\POS_medSupplies\public\images\inventory\`
   - Wait for upload to complete
6. **Set permissions** (if option available):
   - Right-click folder → Permissions → Set to 755
   - Right-click files → Permissions → Set to 644

### Option C: Using SSH (if you have SSH access)

```bash
# On your local Windows machine, open PowerShell and navigate to project
cd C:\xampp\htdocs\POS_medSupplies

# Create a zip file
Compress-Archive -Path public\images\inventory\* -DestinationPath images_inventory.zip

# Upload the zip via FTP/SFTP, then extract on server:
# unzip images_inventory.zip -d /home/username/public_html/public/images/inventory/
# chmod 755 /home/username/public_html/public/images/inventory
# chmod 644 /home/username/public_html/public/images/inventory/*.jpg
```

## Step 2: Verify Upload

After uploading, test by accessing this URL in your browser:
```
https://redempmedsupplies.com/images/inventory/inventory_1766794960_694f26d0981a6.jpg
```

**Note**: Your database stores paths as `images/inventory/filename.jpg`, so the URL will be `https://yourdomain.com/images/inventory/filename.jpg`

If you see the image, the upload was successful! ✅

### Understanding the Path Structure

- **Database stores**: `images/inventory/filename.jpg`
- **Files must be in**: `public/images/inventory/filename.jpg` on your server
- **URL will be**: `https://yourdomain.com/images/inventory/filename.jpg`

This is correct and the code handles this format properly!

## Step 3: Database Mismatch Issue

⚠️ **Important**: The file `itemlist_1766840461_694fd88d07fb1.jpg` you tried to access doesn't exist in your local database. This means your **production database might have different image paths** than your local database.

### Solution Options:

**Option 1: Sync your production database with local database**
- Export your local database and import it to production
- ⚠️ **Warning**: This will overwrite your production data

**Option 2: Check production database image paths**
- Login to phpMyAdmin on Hostinger
- Check the `item_image` column in `item_lists` and `inventory_products` tables
- See which image paths are stored there
- Make sure those image files exist on the server

**Option 3: Clear invalid image paths**
- The code will automatically show placeholder icons for missing images
- You can manually edit items in production to remove invalid image paths

## Step 4: Test Your Application

1. Visit: `https://redempmedsupplies.com/inventory`
2. Check if images are displaying
3. Check browser console (F12) for any 404 errors
4. If you see placeholder icons instead of images, the files might not be uploaded yet or paths don't match

## Troubleshooting

### Images still showing 404 after upload?

1. **Check file permissions:**
   - Folders: 755
   - Files: 644

2. **Verify file paths:**
   - Database paths are: `images/inventory/filename.jpg`
   - Files must be in: `public_html/public/images/inventory/` (most common Laravel setup)
   - OR: `public_html/images/inventory/` (if your domain root points to public_html directly)
   - The URL `https://yourdomain.com/images/inventory/file.jpg` should work if files are uploaded correctly

3. **Check Laravel public path:**
   - If your domain points to `public_html`, files should be in `public_html/public/images/inventory/`
   - If your domain points to `public_html/public`, files should be in `public_html/public/images/inventory/`

4. **Clear cache:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

5. **Check browser cache:**
   - Press Ctrl+F5 to hard refresh
   - Or clear browser cache

### Database paths don't match?

If your production database has different image paths than local:

1. **Export image paths from production:**
   ```sql
   SELECT id, item, item_image FROM item_lists WHERE item_image IS NOT NULL;
   SELECT id, item_name, item_image FROM inventory_products WHERE item_image IS NOT NULL;
   ```

2. **Upload those specific files** if they're different from local

3. **Or update database paths** to match the files you uploaded

## Quick Checklist

- [ ] Connected to Hostinger via FTP/File Manager
- [ ] Created `inventory` folder in `public/images/` directory
- [ ] Uploaded all 8 image files
- [ ] Set folder permissions to 755
- [ ] Set file permissions to 644
- [ ] Tested accessing image URL directly in browser
- [ ] Checked inventory page for images
- [ ] Cleared browser cache (Ctrl+F5)

## Need Help?

If you're still having issues:
1. Check Hostinger error logs
2. Verify the exact directory structure on your server
3. Test accessing an image file directly via URL
4. Check if Laravel's `public` folder is your document root

