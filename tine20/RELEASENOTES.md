
TINE RELEASE NOTES
=====================
                    
  Release:     Mats (2026.11)
  Last change: 2026-01-06

# GENERAL CHANGES (Administrative/Operative)

## IMAP config allowExternalEmail has been refactored to SMTP allowAnyExternalDomains
- imap.allowExternalEmail -> smtp.allowAnyExternalDomains
- smtp.additionaldomains -> smtp.additionalexternaldomains

## PHP 8.2 Support has been dropped
