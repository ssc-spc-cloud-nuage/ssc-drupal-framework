# SSC Drupal Framework

A framework for common modules, styles and functionality shared between SSC projects.

It's currently being used for:

- [MySSC+](https://github.com/ssc-spc-cloud-nuage/myssc-drupalwxt)
- [Serving Government](https://github.com/ssc-spc-cloud-nuage/serving-government)

## Updating the framework dependency in a project's Composer file

To update the framework version in a project, run the following command from the project's root directory in your terminal, replacing `X.X.X` with the actual version that you need:

```
composer require ssc-spc-cloud-nuage/ssc-drupal-framework:"X.X.X"
```

## Identifying the latest tag

To quickly identify which tag was last created from your local environment:

1. Go to the root directory of the framework in your terminal.
2. Run the following command:
  
    ```sh
    git describe --tags --abbrev=0
    ```

3. The name of the latest tag will be printed out.
