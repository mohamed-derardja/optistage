# Render Deployment Guide

This guide walks you through deploying OppMatch to Render using the provided `render.yaml` blueprint.

## Prerequisites

- GitHub repository connected to Render
- Render account (free tier works)
- API keys ready:
  - `GOOGLE_API_KEY` (required for agents)
  - `OPENAI_API_KEY` (optional)
  - `LAMA_API_KEY` (optional)

## Step 1: Deploy Backend, Agents, and Database

1. Go to [Render Dashboard](https://dashboard.render.com)
2. Click **"New"** → **"Blueprint"**
3. Connect your GitHub repository (`mohamed-derardja/optistage`)
4. Render will automatically detect `render.yaml` and create:
   - ✅ **oppmatch-db** (Managed MySQL database)
   - ✅ **oppmatch-backend** (Laravel PHP API)
   - ✅ **oppmatch-agents** (Python Flask service)

5. Click **"Apply"** to create all services

## Step 2: Configure Environment Variables

### Backend Service (`oppmatch-backend`)

After services are created, go to each service's **Environment** tab and add:

**Required:**
- `APP_KEY` - Generate with: `php artisan key:generate --show` (run locally)
- `GOOGLE_API_KEY` - Your Google Gemini API key
- `AGENT_SERVICE_URL` - Update after agents deploy (see Step 3)

**Optional (for payments):**
- `CHARGILY_API_KEY`
- `CHARGILY_API_SECRET`

**Note:** Database credentials (`DB_HOST`, `DB_USERNAME`, etc.) are automatically injected from the database service - no need to set them manually.

### Agents Service (`oppmatch-agents`)

**Required:**
- `GOOGLE_API_KEY` - Your Google Gemini API key

**Optional:**
- `OPENAI_API_KEY`
- `LAMA_API_KEY`

## Step 3: Update Service URLs

After all services deploy, you'll get URLs like:
- Backend: `https://oppmatch-backend-xxxx.onrender.com`
- Agents: `https://oppmatch-agents-xxxx.onrender.com`

### Update Backend Environment Variables:

1. Go to **oppmatch-backend** → **Environment**
2. Update these values:
   - `APP_URL` → `https://oppmatch-backend-xxxx.onrender.com`
   - `AGENT_SERVICE_URL` → `https://oppmatch-agents-xxxx.onrender.com`
   - `FRONTEND_URL` → `https://oppmatch-frontend-xxxx.onrender.com` (after Step 4)
   - `SANCTUM_STATEFUL_DOMAINS` → `oppmatch-frontend-xxxx.onrender.com` (after Step 4)
3. Click **"Save Changes"** - this will trigger a redeploy

## Step 4: Deploy Frontend (Static Site)

Render doesn't support static sites in `render.yaml`, so deploy it manually:

1. In Render Dashboard, click **"New"** → **"Static Site"**
2. Connect your GitHub repository
3. Configure:
   - **Name:** `oppmatch-frontend`
   - **Branch:** `main`
   - **Root Directory:** `frontend`
   - **Build Command:** `npm install --force && npm run build`
   - **Publish Directory:** `dist`
4. Add Environment Variable:
   - `VITE_API_URL` → `https://oppmatch-backend-xxxx.onrender.com/api`
5. Click **"Create Static Site"**

## Step 5: Final Configuration

1. **Update Backend URLs** (from Step 3) with the actual frontend URL
2. **Update Frontend** - Redeploy the frontend if you changed `VITE_API_URL`
3. **Test the deployment:**
   - Backend health: `https://oppmatch-backend-xxxx.onrender.com/api/health` (if available)
   - Agents health: `https://oppmatch-agents-xxxx.onrender.com/health`
   - Frontend: `https://oppmatch-frontend-xxxx.onrender.com`

## Troubleshooting

### Backend Issues

- **500 errors:** Check logs for missing `APP_KEY` or database connection issues
- **Database connection:** Verify database credentials are auto-injected (check Environment tab)
- **Agent timeout:** Increase `AGENT_SERVICE_TIMEOUT` if PDFs are large

### Agents Issues

- **LLM errors:** Verify `GOOGLE_API_KEY` is set correctly
- **Import errors:** Check that all dependencies in `requirements.txt` are installed
- **Timeout:** Free tier has 750 hours/month - upgrade if you hit limits

### Frontend Issues

- **API calls failing:** Verify `VITE_API_URL` points to backend API
- **CORS errors:** Check `SANCTUM_STATEFUL_DOMAINS` includes frontend domain
- **Build fails:** Ensure Node.js version is compatible (check `package.json`)

## Cost Notes

- **Free tier limits:**
  - Services spin down after 15 minutes of inactivity
  - First request after spin-down takes ~30 seconds (cold start)
  - 750 hours/month total across all services
- **Upgrade to paid** for:
  - Always-on services (no spin-down)
  - Faster cold starts
  - More resources

## Updating Services

After pushing changes to GitHub:
- **Backend/Agents:** Render auto-deploys on push to `main`
- **Frontend:** Manual redeploy needed (or enable auto-deploy in settings)
- **Database:** Migrations run automatically via `postDeployCommand`

## Support

If you encounter issues:
1. Check service logs in Render Dashboard
2. Verify all environment variables are set
3. Test endpoints individually (backend, agents, frontend)
4. Check Render status page for outages

