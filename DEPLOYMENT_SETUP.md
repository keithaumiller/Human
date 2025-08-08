# GitHub Secrets Configuration Guide

## Required Secrets for Human Success Module Deployment

To fix the "missing server host" error, you need to configure these secrets in your GitHub repository:

### 1. HOST
- **Name**: `HOST`
- **Value**: Your production server domain or IP
- **Example**: `thetruthperspective.org` or `192.168.1.100`

### 2. USERNAME  
- **Name**: `USERNAME`
- **Value**: SSH username for your server
- **Example**: `ubuntu` or `www-data` or your server username

### 3. PRIVATE_KEY
- **Name**: `PRIVATE_KEY` 
- **Value**: Your SSH private key content (entire key including headers)
- **Example**: 
```
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAFwAAAAdzc2gtcn
...
[your full private key content]
...
-----END OPENSSH PRIVATE KEY-----
```

### 4. HUBGIT_PAT
- **Name**: `HUBGIT_PAT`
- **Value**: GitHub Personal Access Token
- **Scopes needed**: `repo` (for repository access)

## How to Add Secrets:

1. Go to: https://github.com/keithaumiller/Human/settings/secrets/actions
2. Click "New repository secret"
3. Enter the secret name and value
4. Click "Add secret"
5. Repeat for all 4 secrets

## Testing the Deployment:

After adding all secrets, trigger a new deployment by:
1. Making any small change to the code
2. Committing and pushing to master branch
3. The GitHub Action should run successfully

## Troubleshooting:

If you're still having issues:
- Verify the HOST can be reached via SSH
- Test SSH connection manually: `ssh username@host`
- Ensure the PRIVATE_KEY matches the public key on your server
- Check that the GitHub PAT has the correct permissions
