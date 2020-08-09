---
name: Bug report
about: Create a report to help us improve
title: ''
labels: ''
assignees: ''

---

**Describe the bug**
A clear and concise description of what the bug is.

For issues parsing emails or headers, include what the expected result is, what the actual result is, and why you think the behaviour should change:

1. what do the relevant RFCs say
2. if the problem is not described/covered by the RFC, how is a popular mail client handling it (e.g. Thunderbird, Outlook, etc...)
3. or otherwise, how "widespread" is the issue (caused by a popular mail client or mail library for example, but usually # 2 will cover that)

**To Reproduce**
Please include a simplified example email of the issue happening if possible, or otherwise describe the email with as many examples as possible (for instance, an issue in a "From" header may not need a full example email, the example header or a sample with the same issue could be enough... versus a specific issue relating to a multipart message the parser is unable to handle.  Please be cognizant of removing identifiable information from emails, real email addresses, IPs, etc... as necessary, and preferably creating a very simplified email of the issue happening instead of a real email.)

**Versions:**
 - PHP
 - zbateson/mail-mime-parser
 - (anything other relevant version)

**Additional context**
Add any other context about the problem here.
