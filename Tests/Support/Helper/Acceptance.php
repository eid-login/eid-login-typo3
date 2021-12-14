<?php

namespace Helper;

use Codeception\Actor;
use Ecsec\Eidlogin\Dep\OneLogin\Saml2\IdPMetadataParser;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Acceptance extends \Codeception\Module
{
    const URL_SKID_META = 'https://service.skidentity.de/fs/saml/metadata';

    const CERT_INVALID_INSUFFICENT_PUBKEY_LENGTH = 'MIIBKTCB1KADAgECAgRglScoMA0GCSqGSIb3DQEBCwUAMBwxGjAYBgNVBAMMEXRlc3QtY2VydCByc2EgNTEyMB4XDTIxMDUwNzExNDAyNFoXDTIyMDUwNzExNDAyNFowHDEaMBgGA1UEAwwRdGVzdC1jZXJ0IHJzYSA1MTIwXDANBgkqhkiG9w0BAQEFAANLADBIAkEA0LP4k6cbOL1xSs432wj9YB/TB3BkO7j7fxelkqJZNPTtWrMlj1L+3qpPAuGdhXkj689o38Rbk9yOpqq4FlN11QIDAQABMA0GCSqGSIb3DQEBCwUAA0EAo1xf6bJSmcBB9Q2URr7DM22GPeykJGwmAltR3nBeXvauzbS4syF+/cjVzEO+t8wCo+Ws7tfvcLCocUp+cOVZNQ==';
    const CERT_VALID = 'MIIEIzCCAougAwIBAgIEYR4MbzANBgkqhkiG9w0BAQsFADAkMSIwIAYDVQQDDBlUWVBPMyBlSUQtTG9naW4gRXh0ZW5zaW9uMB4XDTIxMDgxOTA3NDY1NVoXDTIzMDgxOTA3NDY1NVowJDEiMCAGA1UEAwwZVFlQTzMgZUlELUxvZ2luIEV4dGVuc2lvbjCCAaIwDQYJKoZIhvcNAQEBBQADggGPADCCAYoCggGBALRlWL164tOoWOHwVKgrE2wUHexDeFXWYmIe+jvKu3fH83wizo0t7ojYxaLmdFLxEEr3Dknfur8zVvWu0H9n8weCNY4q63jsLZIgT7wBT+DQKhOM5jwBMEh3X/LyBcNrFtwWscRQe/hOgAc22TN7sDKtlf4uOr0NH1Wds8uzjq8dy2xTFJzRv6ihi5qKkurnl8mxwB/GVrkWOY/FJzbC1U3HZhcMJxJb1HgGTv6oDBrN02Kb4HwVwlH15/z1g2tstY4lhWT+yXOTmL0K9Q7otYW+f1Sq/nvHk2zq+w+QW6fqJzBvBxVNCnGyi3Q6LIne5mmSBttgmL6hbJZ05/B4c46iR87KDXsUpYivFSCYzIFVSlY113CxOf0qNhJC7cLLIbvLf/Z5LecW659WnzWyFRNYLPIhkzwPytEF70NG6T4R/ckpwMSgg34lVP6SGRKxLjfkWW4ritM/FsRyhqYqfnWfQJN3dBc6fN86I4WuvSqNUqvjN4kNOazwb2DYdG0m0QIDAQABo10wWzAdBgNVHQ4EFgQUL1Uo2U0hYD24i2zYu5EQYruvbUgwHwYDVR0jBBgwFoAUL1Uo2U0hYD24i2zYu5EQYruvbUgwDAYDVR0TAQH/BAIwADALBgNVHQ8EBAMCBaAwDQYJKoZIhvcNAQELBQADggGBAEnYhT654slvGM86kTAfsy5lvhEiCA43QgtlgTDzKUjoBedA+9nyXHhjTGTAXIES9xh6TvN/Utp3knUk2AEr3odWpmv6uQ3herz6w7UVhYkI3/h3fKukTO0fx+LXLvkWm5LuWKCbDbxAP6dcnhH1tTMA96pjMcerTqsUA0p8x6sEfkjY8xAHbQAmKqi91fZWGJxqa77XhDdfAf+S1c3Izmn0BIDUejwTpXBA3HiCbM88bTY+D351t3jXgeyEUY2lWLgVea4cwJwXvpiwDiJPNAQhExyd9gjSfxeEzdbdwaH0ClRplcZBGIV6QmfBNNRXLwAkwCU8ZZzLIFlkJ9If61liHS/BlxEq5nxDtraRYTcf23I0XN1m2RHfr88F4+t4Wnq0olaOAB77gzSflsrTWIGRC5rnrHFzn0+cJ5tN/FbmAHXztzGW6BuTEa8usqjq7Re7bifWA58eWPg9z29CyEzU52wxKZgRCZ5pPjrcPjgl3pnFfAxDdKX7abjHOTqGSw==';

    const CLOUDID_KEY_KEY = 'SKIDJS_DATA.pSm8Yj4veYubBXW';
    const CLOUDID_KEY_VAL = '{"priv":{"type":"ecdsa","secretKey":true,"exponent":"f7b4730a136d048d509ca752f7fbb24b874372b01df3cab6c9dac98413b86871","curve":"c256"},"pub":{"type":"ecdsa","secretKey":false,"point":"80abd2c590d90f11dcac99d94f89d2ae30d43978c402c03f6e02c4ce39e0e3517dbb0d7f850078984305e5bebf073f972911f893fb043d7be59ccfe103778ea4","curve":"c256"},"salt":"V9Z9OTPd7ul0XUbmjddc"}';
    const CLOUDID_DATA_KEY = 'skIdS_data';
    const CLOUDID_DATA_VAL = '{"cloudIdentities":[{"cloudIdentity":"eyJ4NXQjUzI1NiI6ImV1QTFJNW84bjBZNUNvckRPSDViSUdzYmhlVFhTb0tSdWxOX1ZtRTlydDQiLCJhbGciOiJFUzM4NCJ9.eyJpc3MiOiJDPURFLCBTVD1CYXllcm4sIEw9TWljaGVsYXUsIE89ZWNzZWMgR21iSCwgQ049U2tJRGVudGl0eSBDSVAiLCJlbmNyeXB0ZWQtcGF5bG9hZCI6eyJwYXlsb2FkIjoiZXlKNmFYQWlPaUpFUlVZaUxDSjROWFFqVXpJMU5pSTZJbTlPTFhkR04xSkJXRTR6U0ZOa1pHWmlUeTF5ZG5JeVMydDVabEIxWnkxdFoxaFZjRWxKZEZSdVlXc2lMQ0psY0dzaU9uc2lhM1I1SWpvaVJVTWlMQ0pqY25ZaU9pSlFMVE00TkNJc0luZ2lPaUpKYzJ0RlZYZFBObTA0U2taU1VUUjJaRTFUZUVKUUxWTm5kMnczYmtaR2FYQmlWMEpQWjBjemJFVlVjMkZIWWpaclh6TnZObVZPYlRCVVQzVmpXa1ZDSWl3aWVTSTZJbGRUVlRWbmNta3lOalZFVVZaaVl6TTNUa3hRZVhoRGVUTTBUVGx0U2xsVFJWOXZTVVpXTFdkUE5WQnRiMmQ2TUMxRmNFSlJZV2h0T0dGT1IxTmxiM1VpZlN3aVpXNWpJam9pUVRFeU9FZERUU0lzSW1Gc1p5STZJa1ZEUkVndFJWTXJRVEkxTmt0WEluMC5HRmhzZXk2enFGZmVSc3ljTXVtS2wxNWVDQVdtY1pEeC5SRVZCVW5XSkUwMk54eFpjLkFhSUNkRVpMenZTT0lkVXBCZjFrN0NralVTdHBQUTByX0l2cXBsTVN0TlUzN01KX2N5T3A3aW9Kd2FjOURWV2w4eTBNdUtCTXNGWjRSVVByb1lMMk9zY1lXbldBdnVXQVBSZnNvTjdGNWpXZTZTR3UwQi1TZE5UU25ScHVfLWpMSjc5ZS1WQnpUUTF5Y2o2MEFYbTlQTERrSXBHQ2FTS2VXcUlxU0FPTEZqY2lOdm5jNFRCNHRSN0pnT0IzbkpHRy1iRjk4V2hNWDFQT1NHeldiVV9kT0RZZ2xHVmlZNDc3a0k2NFpVc0FlR1g2T2xFRXp5Z0o4Z1JubEhIWFpudEd2NU5zVFNBMTFxM2xUeldLMlA1RGpmc29PdEpKNF9Nc3ZqRXpqNkpOeXlBajlEdGZjYmRUWHdsZWVUaFVmb3lLLWJCUlpTYnB3am93cG1mMjZ2aXI4S0J6MGoyY056V3hnbjMweXRUMDNteGtxUEM0SFdaZ2Z6aEFBdEgxVXlrQzAwSXBycWFMQnpkU3ZxQ3ZjT2dWX3pLV2hFXzFheExSbWhkdTNuVFdROTVfU2MzRllIWndVTzlXWmNSZHNhdzJndnc2Rl9mS2d5TzhGSW1abUthZkZHYndBQTdSN25aM3JHVEJJNVdMaERsNDZJczR4Z2xqODhVQWhzSHlnbFZZU3QtMkxzUGc1TVAwWXBRa0p5cWdwczVKTlVndl96eHlUSHJ3a2hOWFIxRTI2eGliTDNhQ1hTTnEwaFcxQWhrc19Sd2VPWHA4TTg0MWpiLUVkLTVBT1hCVU8xSlpsSzl3aS1QS1R4bzQxQXJmOWZScTgwamFoUFVWdTlnQmFjZ0V0cGxteFY0NTlHWmtaZWJiOEtBTjJUbWZobHpfd1NYVkNiSTlIQ1RucURETVF3OU1nZU81ZEFfeHA3cHh4VGp6UThtdzZQRkhkSFU1QzZpMm54ZHRpVTVUVkRLai1iUzNkY3MzYXB5Zm5mWm1LTl9YVjZRQWhMNUVlUXZucV93TmdtamFSTVBQaktLN0NoaEpIV3E1dVRXcGVfcmdOS21Da2tzZ0xldTB0Q0RIZmdWSTlJTXA1b2gxU3d3amFSUmxqQ2xZOFVUOC1YVzU5emZ5azYzdGpwQ1drcGp5Z09jMjFxcTh5anRlQ2xxb1cydHdkcEQxUU5aZE5DSDBwZ0w1ekd1TE9vZ0txN0RLY21QaDd3YUpNckJudkdkNnNWQklERGtzMDNWMmtpaGpBcTlSV3FPOEM2cE5TZlpVVERwMVZCOGFOM2ptVVplODFVaUdHRmFvZTMzMGJxQUl6UG5Zb3RYNVBXRHFLd0tRT0NTdlNMSTZvNzFENVo4bXpEWW5yQ01PaW5VNUNNZG9MbkdMTDdYdU41SVlsT0Vlc0R1OWhPS01HTzBVaWo5YTRUa3p5X2FUTzBLbHdrZ1dubUNDUk1xQm4zNjdMNms2ODg4cElhUFJEcFppNlh3Qm5JRUd1cUZodWFLLXFlTHNHQ2ppNVNoVEFfNm0wZFVKbmIyYmZYVC1aNXFDb3NVcUtxdXlCSk4wWU95WGp5NFRDU1ptZXQyMWxCRHM0WVIwc0JTUDhvVVdENFlTRTFPenh5eTdLb3lsREV5ZkRfV0t5N2dZQmxUc1VkRHVubHlJd2NFMzByZjdkTHlqWG9idTU0bVk2aVlaNzg0RUlidHFNTldvVjZwYVJnUWZqWEcxS0stUlpVNE0zcjBTTWpjQklmZjVacHFlYWthTGNRRDYwdkc2elc5RkpxLVhLUC11RWJWNlpWOG5uSlpfVDBXV0ZSVUJJYnBRWU9lUGxKRHg4S0dhdUFRUEM5Y2w1aFcxMGFCdklLeGJYTEctcUhyeDlzcFdJRjRmaTcyd3NTZV9mUklhUmlwc21UbWxQUUc4TmZfUVQtQjZmbnVMYTN2amU4ZFVVa2tNLlhMb2ZCdldHckh2ZFFGYXI4UlRBQVEiLCJpbmZvIjp7ImJpbmRpbmdzIjpbeyJjdHgtY2xhc3MiOiJ1cm46b2FzaXM6bmFtZXM6dGM6U0FNTDoyLjA6YWM6Y2xhc3NlczpTb2Z0d2FyZVBLSSIsInR5cCI6Imh0dHA6XC9cL3dzLnNraWRlbnRpdHkuZGVcL2NpXC9iaW5kaW5nXC9rZXlcL3YxLjAifV0sImF0dHJpYnV0ZXMiOlt7Im9yaWdpbiI6eyJwcnQiOiJ1cm46b2lkOjEuMy4xNjIuMTU0ODAuMy4wLjE0IiwiaXNzIjoiTz1CdW5kZXNyZXB1YmxpayBEZXV0c2NobGFuZCwgT1U9QnVuZGVzbWluaXN0ZXJpdW0gZGVzIElubmVybiwgQz1ERSIsInR5cCI6Imh0dHA6XC9cL2JzaS5idW5kLmRlXC9jaWZcL25wYS54bWwifSwibmFtZSI6Imh0dHA6XC9cL3d3dy5za2lkZW50aXR5LmRlXC9hdHRcL2VJZGVudGlmaWVyIiwidmF0IjoxNjM5NDczODkzfSx7Im9yaWdpbiI6eyJwcnQiOiJ1cm46b2lkOjEuMy4xNjIuMTU0ODAuMy4wLjE0IiwiaXNzIjoiTz1CdW5kZXNyZXB1YmxpayBEZXV0c2NobGFuZCwgT1U9QnVuZGVzbWluaXN0ZXJpdW0gZGVzIElubmVybiwgQz1ERSIsInR5cCI6Imh0dHA6XC9cL2JzaS5idW5kLmRlXC9jaWZcL25wYS54bWwifSwibmFtZSI6Imh0dHA6XC9cL3d3dy5za2lkZW50aXR5LmRlXC9hdHRcL0ZpcnN0TmFtZSIsInZhdCI6MTYzOTQ3Mzg5M30seyJvcmlnaW4iOnsicHJ0IjoidXJuOm9pZDoxLjMuMTYyLjE1NDgwLjMuMC4xNCIsImlzcyI6Ik89QnVuZGVzcmVwdWJsaWsgRGV1dHNjaGxhbmQsIE9VPUJ1bmRlc21pbmlzdGVyaXVtIGRlcyBJbm5lcm4sIEM9REUiLCJ0eXAiOiJodHRwOlwvXC9ic2kuYnVuZC5kZVwvY2lmXC9ucGEueG1sIn0sIm5hbWUiOiJodHRwOlwvXC93d3cuc2tpZGVudGl0eS5kZVwvYXR0XC9MYXN0TmFtZSIsInZhdCI6MTYzOTQ3Mzg5M30seyJvcmlnaW4iOnsicHJ0IjoidXJuOm9pZDoxLjMuMTYyLjE1NDgwLjMuMC4xNCIsImlzcyI6Ik89QnVuZGVzcmVwdWJsaWsgRGV1dHNjaGxhbmQsIE9VPUJ1bmRlc21pbmlzdGVyaXVtIGRlcyBJbm5lcm4sIEM9REUiLCJ0eXAiOiJodHRwOlwvXC9ic2kuYnVuZC5kZVwvY2lmXC9ucGEueG1sIn0sIm5hbWUiOiJodHRwOlwvXC93d3cuc2tpZGVudGl0eS5kZVwvYXR0XC9TdHJlZXQiLCJ2YXQiOjE2Mzk0NzM4OTN9LHsib3JpZ2luIjp7InBydCI6InVybjpvaWQ6MS4zLjE2Mi4xNTQ4MC4zLjAuMTQiLCJpc3MiOiJPPUJ1bmRlc3JlcHVibGlrIERldXRzY2hsYW5kLCBPVT1CdW5kZXNtaW5pc3Rlcml1bSBkZXMgSW5uZXJuLCBDPURFIiwidHlwIjoiaHR0cDpcL1wvYnNpLmJ1bmQuZGVcL2NpZlwvbnBhLnhtbCJ9LCJuYW1lIjoiaHR0cDpcL1wvd3d3LnNraWRlbnRpdHkuZGVcL2F0dFwvU3RyZWV0TnVtYmVyIiwidmF0IjoxNjM5NDczODkzfSx7Im9yaWdpbiI6eyJwcnQiOiJ1cm46b2lkOjEuMy4xNjIuMTU0ODAuMy4wLjE0IiwiaXNzIjoiTz1CdW5kZXNyZXB1YmxpayBEZXV0c2NobGFuZCwgT1U9QnVuZGVzbWluaXN0ZXJpdW0gZGVzIElubmVybiwgQz1ERSIsInR5cCI6Imh0dHA6XC9cL2JzaS5idW5kLmRlXC9jaWZcL25wYS54bWwifSwibmFtZSI6Imh0dHA6XC9cL3d3dy5za2lkZW50aXR5LmRlXC9hdHRcL0NpdHkiLCJ2YXQiOjE2Mzk0NzM4OTN9LHsib3JpZ2luIjp7InBydCI6InVybjpvaWQ6MS4zLjE2Mi4xNTQ4MC4zLjAuMTQiLCJpc3MiOiJPPUJ1bmRlc3JlcHVibGlrIERldXRzY2hsYW5kLCBPVT1CdW5kZXNtaW5pc3Rlcml1bSBkZXMgSW5uZXJuLCBDPURFIiwidHlwIjoiaHR0cDpcL1wvYnNpLmJ1bmQuZGVcL2NpZlwvbnBhLnhtbCJ9LCJuYW1lIjoiaHR0cDpcL1wvd3d3LnNraWRlbnRpdHkuZGVcL2F0dFwvWmlwQ29kZSIsInZhdCI6MTYzOTQ3Mzg5M30seyJvcmlnaW4iOnsicHJ0IjoidXJuOm9pZDoxLjMuMTYyLjE1NDgwLjMuMC4xNCIsImlzcyI6Ik89QnVuZGVzcmVwdWJsaWsgRGV1dHNjaGxhbmQsIE9VPUJ1bmRlc21pbmlzdGVyaXVtIGRlcyBJbm5lcm4sIEM9REUiLCJ0eXAiOiJodHRwOlwvXC9ic2kuYnVuZC5kZVwvY2lmXC9ucGEueG1sIn0sIm5hbWUiOiJodHRwOlwvXC93d3cuc2tpZGVudGl0eS5kZVwvYXR0XC9Db3VudHJ5IiwidmF0IjoxNjM5NDczODkzfSx7Im9yaWdpbiI6eyJwcnQiOiJ1cm46b2lkOjEuMy4xNjIuMTU0ODAuMy4wLjE0IiwiaXNzIjoiTz1CdW5kZXNyZXB1YmxpayBEZXV0c2NobGFuZCwgT1U9QnVuZGVzbWluaXN0ZXJpdW0gZGVzIElubmVybiwgQz1ERSIsInR5cCI6Imh0dHA6XC9cL2JzaS5idW5kLmRlXC9jaWZcL25wYS54bWwifSwibmFtZSI6Imh0dHA6XC9cL3d3dy5za2lkZW50aXR5LmRlXC9hdHRcL0RhdGVPZkJpcnRoIiwidmF0IjoxNjM5NDczODkzfV19fSwiZXhwIjoxNjQ3MjQ5OTAxLCJpYXQiOjE2Mzk0NzM5MDEsImp0aSI6IjI4ZGNjOWVmLTIxOWEtNDQ1YS04NGU2LTM3MDcxYjRiMGZiOCIsImlkLXR5cGUiOiJodHRwOlwvXC93cy5za2lkZW50aXR5LmRlXC9jaVwvdjIuMCJ9.Gav3KXOLpr_tr3y4A5bxvYFoybQixxdqE4ljJRnmV-PvDqXectaLKK3Ou46CFeBQUsZrdqiB8AMAxWXJMevDyCi4Rx6JwOpJqWOerYE5Mr--ME9uL5JErnr2JIv2ENUL","bindings":[{"type":"http://ws.skidentity.de/ci/binding/key/v1.0","origin":"WebCrypto","salt":"9MmtkQeRpG0XSeVJ8sKS","keyId":"pSm8Yj4veYubBXW"}],"enabled":true,"salt":"9MmtkQeRpG0XSeVJ8sKS"}]}';
    const CLOUDID_PIN = '12345';

    const CONFIG_TYPE_BLANK = '_blank';
    const CONFIG_TYPE_CONFIGURED = '_configured';
    const CONFIG_TYPE_ACTIVATED = '_activated';

    /**
     * Fetch the Skidentity Metadata
     */
    public function fetchSkidMetaData()
    {
        $metadata = [];
        $metadataRaw = IdPMetadataParser::parseRemoteXML(self::URL_SKID_META);
        $metadataFirst = $metadataRaw['idp'];
        if (array_key_exists('x509cert', $metadataFirst)) {
            $metadata['idp_cert_sign'] = $metadataFirst['x509cert'];
            $metadata['idp_cert_enc'] = $metadataFirst['x509cert'];
        } else {
            $metadata['idp_cert_sign'] = $metadataFirst['x509certMulti']['signing'][0];
            $metadata['idp_cert_enc'] = $metadataFirst['x509certMulti']['encryption'][0];
        }
        $metadata['idp_entity_id'] = $metadataFirst['entityId'];
        $metadata['idp_sso_url'] = $metadataFirst['singleSignOnService']['url'];

        return $metadata;
    }

    /**
     * Get the current config based base URL
     */
    public static function getBaseUrl()
    {
        return self::readBaseUrlFromConfig();
    }

    /**
     * Get the current config based acs URL
     */
    public static function getAcsUrl()
    {
        return self::readBaseUrlFromConfig() . '/eidlogin?tx_eidlogin_saml%5Baction%5D=acsPost&tx_eidlogin_saml%5Bcontroller%5D=Saml';
    }

    /**
     * Get the current config based meta URL
     */
    public static function getMetaUrl()
    {
        return self::readBaseUrlFromConfig() . '/eidlogin?tx_eidlogin_saml%5Baction%5D=meta&tx_eidlogin_saml%5Bcontroller%5D=Saml';
    }

    /**
     * Set a LocalConfiguration of a specific type for the tested TYPO3 instance.
     *
     * Valid types are:
     * - Acceptance::CONFIC_TYPE_BLANK
     * - Acceptance::CONFIC_TYPE_CONFIGURED
     * - Acceptance::CONFIC_TYPE_ACTIVATED
     *
     * @param Actor $I The actor to use
     * @param string $configType The type of confg to set
     * @throws \Exception $e If an invalid configType is given
     */
    public static function setConfiguration(Actor $I, string $configType)
    {
        if ($configType !== self::CONFIG_TYPE_BLANK &&
            $configType !== self::CONFIG_TYPE_CONFIGURED &&
            $configType !== self::CONFIG_TYPE_ACTIVATED
        ) {
            throw new \Exception('invalid config type given');
        }
        // read values from config which differ for each TYPO3 version
        $config = \Codeception\Configuration::config();
        $settings = \Codeception\Configuration::suiteSettings('Acceptance', $config);
        $dbHost = $settings['modules']['enabled'][0]['\Helper\Acceptance']['db_host'];
        $baseUrl = $settings['modules']['enabled'][0]['\Helper\Acceptance']['base_url'];
        $dockerName = $settings['modules']['enabled'][0]['\Helper\Acceptance']['docker_name'];
        // replace values in the configuration template and write it into the container
        // sed uses spaces as delimiters
        $I->runShellCommand('sed "s %DB_HOST% ' . $dbHost . ' g; s %BASE_URL% ' . $baseUrl . ' g" ./Tests/_data/LocalConfiguration.php' . $configType . ' | docker exec -i -u root `docker ps -f "name=^' . $dockerName . '$" -q` tee /var/www/html/typo3conf/LocalConfiguration.php');
        $I->runShellCommand('docker exec -it -u root `docker ps -f "name=^' . $dockerName . '$" -q` chown www-data:www-data /var/www/html/typo3conf/LocalConfiguration.php');
        sleep(3);
    }

    /**
     * Read the base url to use from the currenct config
     */
    private static function readBaseUrlFromConfig()
    {
        $config = \Codeception\Configuration::config();
        $settings = \Codeception\Configuration::suiteSettings('Acceptance', $config);
        $baseUrl = $settings['modules']['enabled'][0]['\Helper\Acceptance']['base_url'];

        return $baseUrl;
    }
}
