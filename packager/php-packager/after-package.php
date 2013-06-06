#!/usr/bin/php
<?php
e("Running puppet to update packager repository...");
x("ssh repository.test.zen.landcareresearch.co.nz 'sudo puppet agent -t'");
