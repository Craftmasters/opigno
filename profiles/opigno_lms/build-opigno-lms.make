api = 2
core = 8.x
; Include the definition for how to build Drupal core directly, including patches:
includes[] = drupal-org-core.make
; Download the Opigno lms install profile and recursively build all its dependencies:
projects[opigno_lms][type] = profile
projects[opigno_lms][download][type] = git
projects[opigno_lms][download][branch] = 8.x-1.x
projects[opigno_lms][download][tag] = 8.x-1.4
