# GitHub Secrets Configuration for Human Success Deployment

## Required GitHub Secrets:

### 1. HOST
- **Secret Name**: `HOST`
- **Secret Value**: `thetruthperspective.org`
- **Description**: Your production server domain

### 2. USERNAME  
- **Secret Name**: `USERNAME`
- **Secret Value**: `ubuntu` (or `www-data` - check with your server admin)
- **Description**: SSH username for your server

### 3. PRIVATE_KEY
- **Secret Name**: `PRIVATE_KEY`
- **Secret Value**: 
```
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAMwAAAAtzc2gtZW
QyNTUxOQAAACBugkd5+824J9op52MTLJk3eEM4ZcDxj/hzgEOhlNtoKgAAALia0xtGmtMb
RgAAAAtzc2gtZWQyNTUxOQAAACBugkd5+824J9op52MTLJk3eEM4ZcDxj/hzgEOhlNtoKg
AAAEBuHOUb/11f5JbS9A4DMTXgVHkePcoDwMhPOPKEF6NENG6CR3n7zbgn2innYxMsmTd4
QzhlwPGP+HOAQ6GU22gqAAAAMGh1bWFuLXN1Y2Nlc3MtZGVwbG95bWVudEB0aGV0cnV0aH
BlcnNwZWN0aXZlLm9yZwECAwQF
-----END OPENSSH PRIVATE KEY-----
```

### 4. HUBGIT_PAT
- **Secret Name**: `HUBGIT_PAT`
- **Secret Value**: `[Generate at https://github.com/settings/tokens]`
- **Description**: GitHub Personal Access Token with `repo` and `workflow` permissions

---

## Server Setup Required:

### Add Public Key to Your Server:

You need to add this public key to your server's authorized_keys file:

```bash
ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIG6CR3n7zbgn2innYxMsmTd4QzhlwPGP+HOAQ6GU22gq human-success-deployment@thetruthperspective.org
```

### How to Add the Public Key:

1. **SSH into your server**: `ssh your-username@thetruthperspective.org`
2. **Add the public key**:
   ```bash
   mkdir -p ~/.ssh
   echo "ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIG6CR3n7zbgn2innYxMsmTd4QzhlwPGP+HOAQ6GU22gq human-success-deployment@thetruthperspective.org" >> ~/.ssh/authorized_keys
   chmod 600 ~/.ssh/authorized_keys
   chmod 700 ~/.ssh
   ```

---

## How to Set GitHub Secrets:

1. **Go to**: https://github.com/keithaumiller/Human/settings/secrets/actions
2. **Click "New repository secret"** for each secret above
3. **Test deployment** by pushing a small change to master branch

---

## Testing the Setup:

After configuring all secrets, test the SSH connection:
```bash
ssh -i ~/.ssh/human_success_deploy_key ubuntu@thetruthperspective.org
```

If successful, push any small change to trigger the deployment workflow!
