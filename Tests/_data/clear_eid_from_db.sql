DELETE FROM `tx_eidlogin_domain_model_attribute`;
DELETE FROM `tx_eidlogin_domain_model_continuedata`;
DELETE FROM `tx_eidlogin_domain_model_eid`;
DELETE FROM `tx_eidlogin_domain_model_message`;
DELETE FROM `tx_eidlogin_domain_model_responsedata`;
UPDATE `fe_users` SET `tx_eidlogin_disablepwlogin` = 0 WHERE `tx_eidlogin_disablepwlogin` = 1;