# Frontend Setup and Testing Guide

## Prerequisites

1. **Backend must be running** on `http://127.0.0.1:8000`
2. **Node.js and npm** installed

## Step 1: Create Environment File

Create a `.env` file in the `frontend` directory with the following content:

```env
# Backend API URL
VITE_API_URL=http://127.0.0.1:8000/api
```

**Important:** 
- The `.env` file must be in the `frontend` directory (same level as `package.json`)
- Restart the dev server after creating/updating the `.env` file

## Step 2: Install Dependencies

```bash
cd frontend
npm install
```

## Step 3: Start the Frontend Development Server

```bash
npm run dev
```

The frontend should start on `http://localhost:5173` (or another port if 5173 is taken).

## Step 4: Test the Frontend

### Test Registration:
1. Navigate to `http://localhost:5173/signup` (or `/signup` route)
2. Fill in the form:
   - Name: Your name
   - Email: A unique email (e.g., `test@example.com`)
   - Password: At least 6 characters
   - Confirm Password: Same as password
3. Click "Sign Up"
4. You should be redirected and logged in automatically

### Test Login:
1. Navigate to `http://localhost:5173/login`
2. Enter your email and password
3. Click "Log In"
4. You should be redirected to the home page

## Troubleshooting

### Issue: "Cannot connect to API" or "Network Error"

**Solution:**
1. Make sure the backend is running:
   ```bash
   cd backend
   php artisan serve
   ```

2. Check the `.env` file has the correct URL:
   ```env
   VITE_API_URL=http://127.0.0.1:8000/api
   ```

3. Restart the frontend dev server after creating/updating `.env`

### Issue: CORS Errors

**Solution:** The backend CORS is already configured to allow all origins. If you still see CORS errors:
1. Check `backend/config/cors.php` - it should allow `*` for origins
2. Clear browser cache
3. Try a different browser or incognito mode

### Issue: 422 Validation Errors

**Solution:** This is normal! The frontend should display the error message. Common issues:
- Email already exists → Use a different email
- Password too short → Use at least 6 characters
- Missing fields → Fill all required fields

### Issue: Import Errors

**Solution:** If you see import errors for `authService`, make sure the import uses the correct case:
```javascript
import { AuthService } from "../services/AuthService";
```

## Testing Checklist

- [ ] Backend server is running on port 8000
- [ ] `.env` file created with `VITE_API_URL`
- [ ] Frontend dependencies installed
- [ ] Frontend dev server started
- [ ] Can access frontend in browser
- [ ] Registration form works
- [ ] Login form works
- [ ] Error messages display correctly
- [ ] Success redirects work

## Development URLs

- **Frontend:** http://localhost:5173
- **Backend API:** http://127.0.0.1:8000/api
- **Backend Test:** http://127.0.0.1:8000/api/test

## Quick Start Commands

```bash
# Terminal 1: Start Backend
cd backend
php artisan serve

# Terminal 2: Start Frontend
cd frontend
npm run dev
```

Then open http://localhost:5173 in your browser!

