# Issues Summary

It's a small script to convert your Github to a CSV

## SETUP
```
git clone git@github.com:matomo-org/issue-summary.git

cd issue-summary

cp Config/config.example.php  Config/config.php

mkdir Output
```

Make sure the Output directory is writable by the user running github-issues-csv.php

## To convert issues to CSV
```
php github-issue-csv.php 
```

##Additional Settings in script

While executing the script you can update the script to include pull requests also by setting `$onlyIssues = false` in github-issue-csv.php.

The default range is of 1 year, which can changed by updating `$startDate and $endDate` variable in github-issue-csv.php.