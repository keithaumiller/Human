# Generate GitHub Personal Access Token (HUBGIT_PAT)

## Instructions:

1. **Go to GitHub Settings**: https://github.com/settings/tokens
2. **Click "Generate new token"** → **"Generate new token (classic)"**
3. **Fill out the form**:
   - **Note**: `Human Success Module Deployment`
   - **Expiration**: `90 days` (or your preference)
   - **Select scopes**:
     - ✅ **repo** (Full control of private repositories)
     - ✅ **workflow** (Update GitHub Action workflows)

4. **Click "Generate token"**
5. **Copy the token immediately** (you won't see it again!)

## Your Generated Token Will Look Like:
```
ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

## Use This Token As:
- **GitHub Secret Name**: `HUBGIT_PAT`
- **GitHub Secret Value**: `ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`

---

**⚠️ IMPORTANT**: Save this token immediately! GitHub will not show it again for security reasons.
