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

    const CLOUDID_KEY_KEY = 'SKIDJS_DATA.Wrlnsy0lFXPuswt';
    const CLOUDID_KEY_VAL = '{"priv":{"type":"ecdsa","secretKey":true,"exponent":"01c2e1a0136efaa05e65af67f51849499a7e34810d31e709fc79a9025bb9747320","curve":"c256"},"pub":{"type":"ecdsa","secretKey":false,"point":"94367a5495cf9cd6eadafcffa2021d1335ef97fad252580b055eb11824332d1b4ee317a46557be06e2e8eb706d7c63fe335bac458597dd9b87531254744552cd","curve":"c256"},"salt":"hVolLPfsfQNcHZUfAmn8"}';
    const CLOUDID_DATA_KEY = 'skIdS_data';
    const CLOUDID_DATA_VAL = '{"cloudIdentities":[{"cloudIdentity":"eyJ4NXQjUzI1NiI6ImV1QTFJNW84bjBZNUNvckRPSDViSUdzYmhlVFhTb0tSdWxOX1ZtRTlydDQiLCJhbGciOiJFUzM4NCJ9.eyJpc3MiOiJDPURFLCBTVD1CYXllcm4sIEw9TWljaGVsYXUsIE89ZWNzZWMgR21iSCwgQ049U2tJRGVudGl0eSBDSVAiLCJlbmNyeXB0ZWQtcGF5bG9hZCI6eyJwYXlsb2FkIjoiZXlKNmFYQWlPaUpFUlVZaUxDSjROWFFqVXpJMU5pSTZJbTlPTFhkR04xSkJXRTR6U0ZOa1pHWmlUeTF5ZG5JeVMydDVabEIxWnkxdFoxaFZjRWxKZEZSdVlXc2lMQ0psY0dzaU9uc2lhM1I1SWpvaVJVTWlMQ0pqY25ZaU9pSlFMVE00TkNJc0luZ2lPaUp5WTFkNlFuSkJla2haV0hKZlVrSlZlazlQU1dkT1ZEUnJWbHA0UWtKa0xUZFZVa04yUjJaWGNUUndSV1U1WTNkSFpEQmZSalZrUjA5eFZGTlRObmhMSWl3aWVTSTZJazlxT1dWSVVXb3dlalY1ZVVwWVdIQmlOSGt0VlUxRVgwUnZlblE1UTBOTlkyTlhUbmhrUm1KMWNGUk1YekZyUmpoNWFEZFhibmRrVEZoeFpHUmFWbFFpZlN3aVpXNWpJam9pUVRFeU9FZERUU0lzSW1Gc1p5STZJa1ZEUkVndFJWTXJRVEkxTmt0WEluMC50SWJoeWtZaW5EdTNrM25LRzVWZHdmQmE5UzliZUFBbS5Dc3J4SmwwSnZqZ2ZxeTJ4Lk9yX1FMMThjT05qWHdZRFZ1bE5GaXBIV0Frbi1wQlIzT2k2US1saHA5NjFTRXh3VFBlbmVTNF9qcjJRaHktVUZId0hPNENnaGt3T0xNSGxSWnI1dmhRZWxJc0s5czZJbVZLZ245SFpfRk8teHQ3QTZob1ZDc2xDWmZ4WVY2NGJyZnN0YjEzemZKajNCZ0tGNnFETFJHZG1pVC16UGZJQy1vTkRRN2J1eGxlRkhsTWlWN3dWS1J2a2p4WVlTdkJHakdQZExIRmdUUEtRU01FcE1aLU9RMzZlbE51V0U2S0FZel9JMmw3MG5zcEROcC1kelpVWDZJYXV0dlg3Y2NVeVBveTBmTUkyME5UaGZtbndKaXQwLXp1SW9HZ3lHX0hENnFrRzE4bVdpaE9CV3J5UGx6akFTNjR0STVua1lSUHg4MnZzR1d0SURyR1p0V2Q5Sjh1LXBIek9NQkJ3b0tTTFpIMlI5djNtZ2k5Mi1LM05wNXgtb0F0RUwxd1VhWGk0U0lOMFdfQ0Q5OXFoWmxHVEs5bFhLdXc4SmpGTy1wQTRNVkZGME9Xa19lWTJrUVZMUDRoeFNTUHpKWDljN1Nkbl8wVGloS0lvT0hZTFpYM1FXcDlpc25sTzZTYnlxeXdySzdqZ25ZZXZoS2NHaFkzbl9ndXBmdTVJRHF4akR0WjJkZy1iQlRMWmd1TDJmb1lpR3M4TUZuc0JHU00tdDFybk9LVTFTR2ZlQkRac2VyNGhyUi1uc0djM0xwQ3NxOVUtR016ZWtHREFpeEROMzlyeGRtdGtCN1JZbjJlMjFCWlBMbHNHaV94em16U29xaW1VaDRUeFNyeWRYTEJFT0RBREhJUW9jZVBubzBZeXJ0Ykdhd2s5YXNXY1VVeXhyT1N5SC1FMXhjVEw1U0lBbVVrbTR4YnR4VHAzd3ozQWVNUlYzSGRGQjJtZXp5UHFhN0dPNE9ST0lwcGhWa3RZMnZzWkFBZ2MzME9qTUVvbndzcm1taEhXd0Vhcm1xZlJ6VTdNMzdOZlh1d1FwXy1saDU5aXhBMHFXQkFuZ0M4bVNhZHZVTFBEUXVDT2FybnFkY0NPOU9TTW5WT0V2V2RGWVFhRVY4cnJLWVJxMU1kMy1FU3dWSGtLR2hkdnJ6RGtVMUZoTk9TcVlTVEhrbWdYTUtZM1gwUllvdXY2VTlYa0JsdWxrby1mcEF1bGw1QktzRGQ5dm1GUUdqZk9Jd29wbS1wVzdaV0JWakV3Vm9KZkJkS2xlMGNKWHAxSHNsazlLOG5sZnNVWHg5NzhUc1lGaUY4a184NjdrN2VkbWk5aDVhSXJhQ0tFbjkwb2QxdTk0OUpnX1NlMkRxYlVVbzlveDBqNkZSRTRfT1FiUTZQOGFEbUVscXptc1FfaWhHUGJVc3IwZUVVOG5pQ0xTN0RvUHNGbURlQkdUVVhMUm5FaDZuSi1mc2F0MEtqZmw3SGVMakZhc1FicnlzNlNUa3ZyNDVPSm1BUFFVdGhDZENhM2dFSEJYaWs5Vks2RWlqV0VvZnZPdlJpS3hobkc3aW1Jb1FfRHJHQmhYOWhEZThGUUZOa1dKSUc3WTBFMXdDejZQYXJzUjNoZUFCdzlxaHJyTGE3dkxiSnM2TlVEbUxVUlVVM0lwWm13Y21LYXU5cTV3dWpUZFRUWXJ6X2tGdVVQR1FQcGlBb2kyVGJvQm5hWm9lYTRMVFY0R1FLRlFPYjRLMTl1RkFSUld2RHVlVHdCOWxubVRiRktsRVNOTmY1dEc5Ty1hQjF4YlpOMEpDaXRfMWV4ckJIWWNPekNOYlVINmlsZDduYUpRVlFfMG4zaFAtSDFCbmFqZEI3TTJwcDNNSGpJSWtvTWRtajdWTFVYSmk1VTEyX0pyVlVvbUhoSG9OLUctMEhmVVRoWm1mQW00NEVDbkZha2tPZDVYMGtCY2dNNEtqaGpsQmNxRk9nLnBHMGJZWEJ6V0R3X040ZTJoTG1aV1EiLCJpbmZvIjp7ImJpbmRpbmdzIjpbeyJjdHgtY2xhc3MiOiJ1cm46b2FzaXM6bmFtZXM6dGM6U0FNTDoyLjA6YWM6Y2xhc3NlczpTb2Z0d2FyZVBLSSIsInR5cCI6Imh0dHA6XC9cL3dzLnNraWRlbnRpdHkuZGVcL2NpXC9iaW5kaW5nXC9rZXlcL3YxLjAifV0sImF0dHJpYnV0ZXMiOlt7Im9yaWdpbiI6eyJwcnQiOiJ1cm46b2lkOjEuMy4xNjIuMTU0ODAuMy4wLjE0IiwiaXNzIjoiTz1CdW5kZXNyZXB1YmxpayBEZXV0c2NobGFuZCwgT1U9QnVuZGVzbWluaXN0ZXJpdW0gZGVzIElubmVybiwgQz1ERSIsInR5cCI6Imh0dHA6XC9cL2JzaS5idW5kLmRlXC9jaWZcL25wYS54bWwifSwibmFtZSI6Imh0dHA6XC9cL3d3dy5za2lkZW50aXR5LmRlXC9hdHRcL2VJZGVudGlmaWVyIiwidmF0IjoxNjI5NDY3MjE1fSx7Im9yaWdpbiI6eyJwcnQiOiJ1cm46b2lkOjEuMy4xNjIuMTU0ODAuMy4wLjE0IiwiaXNzIjoiTz1CdW5kZXNyZXB1YmxpayBEZXV0c2NobGFuZCwgT1U9QnVuZGVzbWluaXN0ZXJpdW0gZGVzIElubmVybiwgQz1ERSIsInR5cCI6Imh0dHA6XC9cL2JzaS5idW5kLmRlXC9jaWZcL25wYS54bWwifSwibmFtZSI6Imh0dHA6XC9cL3d3dy5za2lkZW50aXR5LmRlXC9hdHRcL0ZpcnN0TmFtZSIsInZhdCI6MTYyOTQ2NzIxNX0seyJvcmlnaW4iOnsicHJ0IjoidXJuOm9pZDoxLjMuMTYyLjE1NDgwLjMuMC4xNCIsImlzcyI6Ik89QnVuZGVzcmVwdWJsaWsgRGV1dHNjaGxhbmQsIE9VPUJ1bmRlc21pbmlzdGVyaXVtIGRlcyBJbm5lcm4sIEM9REUiLCJ0eXAiOiJodHRwOlwvXC9ic2kuYnVuZC5kZVwvY2lmXC9ucGEueG1sIn0sIm5hbWUiOiJodHRwOlwvXC93d3cuc2tpZGVudGl0eS5kZVwvYXR0XC9MYXN0TmFtZSIsInZhdCI6MTYyOTQ2NzIxNX0seyJvcmlnaW4iOnsicHJ0IjoidXJuOm9pZDoxLjMuMTYyLjE1NDgwLjMuMC4xNCIsImlzcyI6Ik89QnVuZGVzcmVwdWJsaWsgRGV1dHNjaGxhbmQsIE9VPUJ1bmRlc21pbmlzdGVyaXVtIGRlcyBJbm5lcm4sIEM9REUiLCJ0eXAiOiJodHRwOlwvXC9ic2kuYnVuZC5kZVwvY2lmXC9ucGEueG1sIn0sIm5hbWUiOiJodHRwOlwvXC93d3cuc2tpZGVudGl0eS5kZVwvYXR0XC9TdHJlZXQiLCJ2YXQiOjE2Mjk0NjcyMTV9LHsib3JpZ2luIjp7InBydCI6InVybjpvaWQ6MS4zLjE2Mi4xNTQ4MC4zLjAuMTQiLCJpc3MiOiJPPUJ1bmRlc3JlcHVibGlrIERldXRzY2hsYW5kLCBPVT1CdW5kZXNtaW5pc3Rlcml1bSBkZXMgSW5uZXJuLCBDPURFIiwidHlwIjoiaHR0cDpcL1wvYnNpLmJ1bmQuZGVcL2NpZlwvbnBhLnhtbCJ9LCJuYW1lIjoiaHR0cDpcL1wvd3d3LnNraWRlbnRpdHkuZGVcL2F0dFwvU3RyZWV0TnVtYmVyIiwidmF0IjoxNjI5NDY3MjE1fSx7Im9yaWdpbiI6eyJwcnQiOiJ1cm46b2lkOjEuMy4xNjIuMTU0ODAuMy4wLjE0IiwiaXNzIjoiTz1CdW5kZXNyZXB1YmxpayBEZXV0c2NobGFuZCwgT1U9QnVuZGVzbWluaXN0ZXJpdW0gZGVzIElubmVybiwgQz1ERSIsInR5cCI6Imh0dHA6XC9cL2JzaS5idW5kLmRlXC9jaWZcL25wYS54bWwifSwibmFtZSI6Imh0dHA6XC9cL3d3dy5za2lkZW50aXR5LmRlXC9hdHRcL0NpdHkiLCJ2YXQiOjE2Mjk0NjcyMTV9LHsib3JpZ2luIjp7InBydCI6InVybjpvaWQ6MS4zLjE2Mi4xNTQ4MC4zLjAuMTQiLCJpc3MiOiJPPUJ1bmRlc3JlcHVibGlrIERldXRzY2hsYW5kLCBPVT1CdW5kZXNtaW5pc3Rlcml1bSBkZXMgSW5uZXJuLCBDPURFIiwidHlwIjoiaHR0cDpcL1wvYnNpLmJ1bmQuZGVcL2NpZlwvbnBhLnhtbCJ9LCJuYW1lIjoiaHR0cDpcL1wvd3d3LnNraWRlbnRpdHkuZGVcL2F0dFwvWmlwQ29kZSIsInZhdCI6MTYyOTQ2NzIxNX0seyJvcmlnaW4iOnsicHJ0IjoidXJuOm9pZDoxLjMuMTYyLjE1NDgwLjMuMC4xNCIsImlzcyI6Ik89QnVuZGVzcmVwdWJsaWsgRGV1dHNjaGxhbmQsIE9VPUJ1bmRlc21pbmlzdGVyaXVtIGRlcyBJbm5lcm4sIEM9REUiLCJ0eXAiOiJodHRwOlwvXC9ic2kuYnVuZC5kZVwvY2lmXC9ucGEueG1sIn0sIm5hbWUiOiJodHRwOlwvXC93d3cuc2tpZGVudGl0eS5kZVwvYXR0XC9Db3VudHJ5IiwidmF0IjoxNjI5NDY3MjE1fSx7Im9yaWdpbiI6eyJwcnQiOiJ1cm46b2lkOjEuMy4xNjIuMTU0ODAuMy4wLjE0IiwiaXNzIjoiTz1CdW5kZXNyZXB1YmxpayBEZXV0c2NobGFuZCwgT1U9QnVuZGVzbWluaXN0ZXJpdW0gZGVzIElubmVybiwgQz1ERSIsInR5cCI6Imh0dHA6XC9cL2JzaS5idW5kLmRlXC9jaWZcL25wYS54bWwifSwibmFtZSI6Imh0dHA6XC9cL3d3dy5za2lkZW50aXR5LmRlXC9hdHRcL0RhdGVPZkJpcnRoIiwidmF0IjoxNjI5NDY3MjE1fV19fSwiZXhwIjoxNjM3MjQzMjI0LCJpYXQiOjE2Mjk0NjcyMjQsImp0aSI6IjkwYTk4NzI0LWU1N2UtNGZkYy1iYzBjLWM2NGJhMDFkODYzNyIsImlkLXR5cGUiOiJodHRwOlwvXC93cy5za2lkZW50aXR5LmRlXC9jaVwvdjIuMCJ9.Eb3dFW0tvPq6mwA7632TQ1pkjZA-jF_toBz7Ibk5dZuqa7w-FxTvEB6FF0UfM-4MH0I0PVpSlxTU7hmYvVjUgE7ePXcJvGBA6idqIEK30PeIRH8xYX0ryfy78GSrXQfA","bindings":[{"type":"http://ws.skidentity.de/ci/binding/key/v1.0","origin":"WebCrypto","salt":"08QQanxDwm4GRqvV5lm9","keyId":"Wrlnsy0lFXPuswt"}],"enabled":true,"salt":"08QQanxDwm4GRqvV5lm9"}]}';
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
