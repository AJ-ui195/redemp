# Fix Gradle "-classpath requires class path specification" Error

## Problem
When running `npx expo run:android`, you get the error:
```
Error: -classpath requires class path specification
```

## Solution

### Option 1: Set JAVA_HOME Environment Variable (Recommended)

1. **Find your Java installation path:**
   - Open Command Prompt and run: `where java`
   - This will show something like: `C:\Program Files\Common Files\Oracle\Java\javapath\java.exe`
   - Navigate to the parent directory to find the actual Java installation (usually `C:\Program Files\Java\jdk-17` or similar)

2. **Set JAVA_HOME:**
   - Open System Properties → Environment Variables
   - Under "System variables", click "New"
   - Variable name: `JAVA_HOME`
   - Variable value: Your Java installation path (e.g., `C:\Program Files\Java\jdk-17`)
   - Click OK

3. **Add to PATH (if not already there):**
   - Edit the `Path` variable
   - Add: `%JAVA_HOME%\bin`
   - Click OK

4. **Restart your terminal/IDE** and try again:
   ```bash
   npx expo run:android
   ```

### Option 2: Use Expo Go Instead (Easier)

If you just want to test the app, use Expo Go instead of building:

```bash
npx expo start
```

Then scan the QR code with Expo Go app on your phone.

### Option 3: Use Android Studio

1. Open Android Studio
2. Open the project: `Cashier/cashierScan/android`
3. Let Gradle sync
4. Run the app from Android Studio

### Option 4: Check Java Version Compatibility

Make sure you have Java 17 or 21 installed (recommended for React Native):
```bash
java -version
```

If you have an older version, download Java 17 from:
https://adoptium.net/

### Option 5: Clear Gradle Cache

```bash
cd Cashier\cashierScan\android
.\gradlew.bat clean
.\gradlew.bat --stop
```

Then try again:
```bash
cd ..
npx expo run:android
```

## Quick Test

To verify Java is working correctly:
```bash
java -version
javac -version
```

Both should show Java 17 or higher.
