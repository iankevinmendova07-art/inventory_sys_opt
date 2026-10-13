# Third Party Notices

The standalone package includes the following separately licensed components. License texts are copied beside each UI dependency in `assets/vendor` and with the bundled runtimes in the installer payload.

| Component | Pinned version | License / notice |
|---|---:|---|
| PHP for Windows | 8.3.30 | PHP License 3.01; see `tools/installer/PHP-LICENSE.txt` and the PHP redistribution notice in the payload. |
| MySQL Community Server | 8.4.3 | GNU GPL v2 with MySQL additional permissions; see `runtime/mysql/LICENSE` in the payload. |
| Bootstrap | 5.3.0 | MIT; `assets/vendor/bootstrap/LICENSE`. |
| jQuery | 3.7.0 | MIT; `assets/vendor/jquery/LICENSE.txt`. |
| SweetAlert2 | 11.22.0 | MIT; `assets/vendor/sweetalert2/LICENSE`. |
| DataTables | 1.13.6 | MIT; `assets/vendor/datatables/LICENSE.txt`. |
| Chart.js | 4.5.0 | MIT; `assets/vendor/chartjs/LICENSE.md`. |
| Bootstrap Icons | 1.10.5 | MIT; `assets/vendor/bootstrap-icons/LICENSE`. |
| Font Awesome Free | 6.4.0 | CSS/code MIT, icon fonts SIL OFL 1.1, icon artwork CC BY 4.0; `assets/vendor/fontawesome/LICENSE.txt`. |

MySQL Community Server is dual licensed. Oracle documents its Community Server under GPL v2 and its additional permissions in the server distribution license. Anyone conveying the bundled MySQL binaries outside a private deployment must review and satisfy the applicable GPL source and notice requirements for the exact unmodified 8.4.3 binaries. The installer is not to be represented as a legally cleared public redistribution until that source-compliance step is complete. See [MySQL 8.4 licensing information](https://dev.mysql.com/doc/refman/8.4/en/introduction.html).

The MySQL 8.4.3 Windows ZIP used for the bundled runtime overlay is available from [Oracle's MySQL download archive](https://dev.mysql.com/get/Downloads/MySQL-8.4/mysql-8.4.3-winx64.zip). Verify the archive and its checksum against Oracle's release information when reproducing the build.

The package should retain these notices and the component license files when redistributed. See [PHP redistribution guidance](https://www.php.net/license/distrib-guidelines-code.php), [Bootstrap license FAQ](https://getbootstrap.com/docs/4.0/about/license/), and [DataTables MIT license](https://datatables.net/license/mit.html).
