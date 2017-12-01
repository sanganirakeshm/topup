<!-- Sales Report -->

INSERT INTO `dhiportal`.`permission_category` (`id`, `name`) VALUES (NULL, 'Sales Report');
INSERT INTO `dhiportal`.`permission` (`id`, `category_id`, `name`, `code`) VALUES (NULL, '47', 'Allow to view sales report', 'sales_report_view');

INSERT INTO `dhiportal`.`permission` (
`id` ,
`category_id` ,
`name` ,
`code`
)
VALUES (
NULL , '47', 'Allow to export pdf sales report', 'sales_report_export_pdf'), 
(
NULL , '47' , 'Allow to export csv sales report', 'sales_report_export_csv'),
(
NULL , '47' , 'Allow to export ecel sales report', 'sales_report_export_excel'), 
(
NULL, '47', 'Allow to view sales report', 'sales_report_view');




<!-- mac-Address -->

INSERT INTO `dhiportal`.`permission` (`id`, `category_id`, `name`, `code`) VALUES (NULL, '42', 'Allow to Transfer mac address', 'mac_address_transfer');



<!-- Package -->


INSERT INTO `dhiportal`.`permission_category` (`id`, `name`) VALUES (NULL, 'Package');

INSERT INTO `dhiportal`.`permission` (`id`, `category_id`, `name`, `code`) VALUES (NULL, '43', 'Allow to view package list', 'package_list');


<!-- Set-Top-Box -->


INSERT INTO `dhiportal`.`permission_category` (
`id` ,
`name`
)
VALUES (
NULL , 'Set-Top-Box'
);

INSERT INTO `dhiportal`.`permission` (`id`, `category_id`, `name`, `code`) VALUES (NULL, '44', 'Allow to add set-top-box', 'set_top_box_create'), (NULL, '44', 'Allow to update set-top-box', 'set_top_box_update '), (NULL, '44', 'Allow to delete set-top-box', 'set_top_box_delete'), (NULL, '44', 'Allow to return set-top-box', 'set_top_box_return');

INSERT INTO `dhiportal`.`permission` (`id`, `category_id`, `name`, `code`) VALUES (NULL, '44', 'Allow to view set-top-box', 'set_top_box_list');
 


<!--Promo-Code -->

INSERT INTO `dhiportal`.`permission_category` (`id`, `name`) VALUES (NULL, 'Promo-Code');

INSERT INTO `dhiportal`.`permission` (`id`, `category_id`, `name`, `code`) VALUES (NULL, '45', 'Allow to view promo-code', 'promo_code_list'), (NULL, '45', 'Allow to add promo-code ', 'promo_code_create'), (NULL, '45', 'Allow to update promo-code ', 'promo_code_update'), (NULL, '45', 'Allow to delete promo-code', 'promo_code_delete'), (NULL, '45', 'Allow to export csv for promo-code', 'promo_code_export_csv'), (NULL, '45', 'Allow to export pdf for promo-code', 'promo_code_export_pdf');
INSERT INTO `dhiportal`.`permission` (`id`, `category_id`, `name`, `code`) VALUES (NULL, '45', 'Allow to enable/disable promo code', 'promo_code_enable_disable'), (NULL, '45', 'Allow to view customer', 'promo_code_view_customer');

<!-- Sales Report -->

INSERT INTO `dhiportal`.`permission_category` (`id`, `name`) VALUES (NULL, 'Sales Report');
INSERT INTO `dhiportal`.`permission` (`id`, `category_id`, `name`, `code`) VALUES (NULL, '43', 'Allow to view sales report', 'sales_report_view');

INSERT INTO `dhiportal`.`permission` (
`id` ,
`category_id` ,
`name` ,
`code`
)
VALUES (
NULL , '43', 'Allow to export pdf sales report', 'sales_report_export_pdf'), 
(
NULL , '43' , 'Allow to export csv sales report', 'sales_report_export_csv'),
(
NULL , '43' , 'Allow to export ecel sales report', 'sales_report_export_excel'), 
(
NULL, '43', 'Allow to view sales report', 'sales_report_view');

