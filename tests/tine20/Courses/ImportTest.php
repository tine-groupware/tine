<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Courses
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2023-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test class for Courses_Import_...
 */
class Courses_ImportTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);

        // clean up after other tests :-/
        Courses_Config::getInstance()->internet_group = null;
        Courses_Config::getInstance()->internet_group_filtered = null;
        Courses_Controller_Course::destroyInstance();
    }

    public function tearDown(): void
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::ACCOUNT_TWIG, [
            Tinebase_Config::ACCOUNT_TWIG_LOGIN => '{{ account.accountFirstName|transliterate|removeSpace|accountLoginChars|trim[0:1]|lower }}{{ account.accountLastName|transliterate|removeSpace|accountLoginChars|lower }}',
        ]);

        parent::tearDown();
    }

    protected function _setupDepartments()
    {
        $arr = [
            'bfs' => ['kas', 'mwp', 'sc'],
            'bs' => ['av', 'dp', 'fo', 'kd', 'meg', 'mf', 'mk', 'mkh'],
            'bv' => ['avm', 'avu', 'va'],
            'os' => ['bos', 'fos'],
            'sonst' => null,
            'test' => ['tst']
        ];
        Courses_Config::getInstance()->{Courses_Config::COURSE_DEPARTMENT_MAPPING} = $arr;
        foreach ($arr as $dep => $conf) {
            Tinebase_Department::getInstance()->create(new Tinebase_Model_Department(['name' => $dep]));
        }
    }

    public function testDivisImport()
    {
        $this->_skipIfLDAPBackend('FIXME email user sql connection timeout...');

        $this->_setupDepartments();

        Tinebase_Config::getInstance()->set(Tinebase_Config::ACCOUNT_TWIG, [
            Tinebase_Config::ACCOUNT_TWIG_LOGIN => '{% if course.name %}{{ course.name }}-{% endif %}{{ account.accountFirstName|transliterate|removeSpace|accountLoginChars|trim[0:1]|lower }}.{{ account.accountLastName|transliterate|removeSpace|accountLoginChars|lower }}',
        ]);

        $fileManager = Filemanager_Controller_Node::getInstance();
        $node = $fileManager->createNodes(['/shared/unittest'], [Tinebase_Model_Tree_FileObject::TYPE_FOLDER])->getFirstRecord();
        file_put_contents('tine20://' . ($path = Tinebase_FileSystem::getInstance()->getPathOfNode($node->getId(), true))
            . '/import.csv',
<<<CSV
Vorname;Nachname;Rufname;Geburtstag;Geschlecht;Kürzel;Schulen;Stammschule;Klassen;Klassennamen;Angebote;Manuelle Gruppen;Anmeldekennung;E-Mail-Adressen der weiteren Schulen;Status;Rolle;Quelle;Interne ID;Kurze ID;Gültig ab;Gültig bis;Löschdatum
Benisa Ben;Boateng;;10.09.2002;Keine Angabe;;bs17;bs17;bs17-bos32-Klasse-2024/2025;bos32;;;benisa.boateng@bs17.hamburg.de;benisa.boateng@bs17.hamburg.de;Aktiv;Lernende;DiViS;0580ed78-3bcd-4af6-9226-f0057d19ea77;000000019863;01.08.2024;31.07.2025;28.01.2026
Lilly;Birker;;22.09.2004;Keine Angabe;;bs17;bs17;bs17-av31-Klasse-2024/2025;av31;bs17-4. Halbjahr DMP 2.Hj-Angebot-2025/2026,bs17-av31 LF#10.0987 2.Hj-Angebot-2025/2026,bs17-av31 LF#07.0987 2.Hj-Angebot-2025/2026,bs17-av31 SuK 2.Hj-Angebot-2025/2026,bs17-av31 WuG 2.Hj-Angebot-2025/2026,bs17-av31 FachE 2.Hj-Angebot-2025/2026,bs17-av31 PC 2.Hj-Angebot-2025/2026,bs17-av31 LF#11.0987 2.Hj-Angebot-2025/2026,bs17-av31 LF#05.0987 2.Hj-Angebot-2025/2026,bs17-av31 LF#06.0987 2.Hj-Angebot-2025/2026;;lilly.birker@bs17.hamburg.de;lilly.birker@bs17.hamburg.de;Aktiv;Lernende;DiViS;28aaca23-f058-45ad-ac2c-d1987b744a70;000000017417;01.08.2023;31.01.2026;31.07.2026
Agit;Binboga;;26.05.1999;Keine Angabe;;bs17;bs17;;;;;agit.binboga@bs17.hamburg.de;agit.binboga@bs17.hamburg.de;Inaktiv;Lernende;DiViS;2a8f329b-be68-43bb-a360-2bbe55142de1;000000029246;01.08.2023;30.01.2025;30.07.2025
Kayhan;Betanay;Kayhan;12.09.2006;Keine Angabe;;bs17;bs17;bs17-avm31-Klasse-2024/2025;avm31;bs17-avm31 LF#03.1222 1.Hj-Angebot-2024/2025,bs17-avm31 GeBe 1.Hj-Angebot-2024/2025,bs17-avm31 LF#01.1222 1.Hj-Angebot-2024/2025,bs17-avm31 SuK 1.Hj-Angebot-2024/2025,bs17-avm31 Ma 1.Hj-Angebot-2024/2025,bs17-avm31 WuLD 1.Hj-Angebot-2024/2025,bs17-avm31 LF#02.1222 1.Hj-Angebot-2024/2025,bs17-avm31 FachE 1.Hj-Angebot-2024/2025,bs17-avm31 LF#04.1222 1.Hj-Angebot-2024/2025;;kayhan.betanay@bs17.hamburg.de;kayhan.betanay@bs17.hamburg.de;Aktiv;Lernende;DiViS;2df29385-534f-449f-a4a6-785844333fc5;000000014465;01.02.2023;31.07.2025;28.01.2026
Denys;Biriukov;;07.02.2006;Keine Angabe;;bs17;bs17;bs17-avm33-Klasse-2024/2025;avm33;bs17-avm33 LF#02.1222 1.Hj-Angebot-2024/2025,bs17-avm33 WuLD 1.Hj-Angebot-2024/2025,bs17-avm33 SuK 1.Hj-Angebot-2024/2025,bs17-avm33 LF#01.1222 1.Hj-Angebot-2024/2025,bs17-avm33 LF#04.1222 1.Hj-Angebot-2024/2025,bs17-avm33 Ma 1.Hj-Angebot-2024/2025,bs17-#mint-Angebot-2024/2025,bs17-avm33 GeBe 1.Hj-Angebot-2024/2025,bs17-avm33 LF#03.1222 1.Hj-Angebot-2024/2025,bs17-avm33 FachE 1.Hj-Angebot-2024/2025;;denys.biriukov@bs17.hamburg.de;denys.biriukov@bs17.hamburg.de;Aktiv;Lernende;DiViS;317f4409-798f-4d23-a260-07bdebab84ef;000000013549;01.08.2022;31.07.2025;28.01.2026
Antonia;Bloh;;17.11.2004;Keine Angabe;;bs17;bs17;bs17-mk44-Klasse-2024/2025;mk44;bs17-mk44 LF#01 Ausbildungsbetrieb-Angebot-2024/2025,bs17-mk44 LF#12.0995 2.Hj-Angebot-2025/2026,bs17-mk44 LF#07 Komm Instrumente I-Angebot-2025/2026,bs17-mk4 SuK Ha 1.+2.Hj-Angebot-2025/2026,bs17-mk44 LF#06 Marketingprojekt-Angebot-2025/2026,bs17-mk44 LF#02 Rewe-Angebot-2025/2026,bs17-mk44 LF#04 Märkte analysieren-Angebot-2025/2026,bs17-mk44 LF#03 Beschaffung-Angebot-2025/2026,bs17-MK4 1.+2. Hj Video-Angebot-2025/2026;;antonia.bloh@bs17.hamburg.de;antonia.bloh@bs17.hamburg.de;Aktiv;Lernende;DiViS;33ca85a1-c912-4b09-9684-75d44faf6832;000000058334;01.08.2024;31.01.2027;31.07.2027
Yevhenii;Bieliakov;Yevhenii;10.12.2005;Keine Angabe;;bs17;bs17;bs17-avm33-Klasse-2024/2025;avm33;bs17-avm33 LF#02.1222 1.Hj-Angebot-2024/2025,bs17-avm33 WuLD 1.Hj-Angebot-2024/2025,bs17-avm33 SuK 1.Hj-Angebot-2024/2025,bs17-avm33 LF#01.1222 1.Hj-Angebot-2024/2025,bs17-avm33 LF#04.1222 1.Hj-Angebot-2024/2025,bs17-avm33 Ma 1.Hj-Angebot-2024/2025,bs17-#mint-Angebot-2024/2025,bs17-avm33 GeBe 1.Hj-Angebot-2024/2025,bs17-avm33 LF#03.1222 1.Hj-Angebot-2024/2025,bs17-avm33 FachE 1.Hj-Angebot-2024/2025;;yevhenii.bieliakov@bs17.hamburg.de;yevhenii.bieliakov@bs17.hamburg.de;Aktiv;Lernende;DiViS;379e679c-7344-4c42-ba20-0e1338606c6e;000000017086;01.08.2023;31.07.2025;28.01.2026
Zoe;Blome;;05.11.1997;Keine Angabe;;bs17;bs17;bs17-av31-Klasse-2024/2025;av31;bs17-av31 LF#10.0987 2.Hj-Angebot-2025/2026,bs17-4. Halbjahr VIP 2.Hj-Angebot-2025/2026,bs17-av31 LF#07.0987 2.Hj-Angebot-2025/2026,bs17-av31 SuK 2.Hj-Angebot-2025/2026,bs17-av31 WuG 2.Hj-Angebot-2025/2026,bs17-av31 FachE 2.Hj-Angebot-2025/2026,bs17-av31 PC 2.Hj-Angebot-2025/2026,bs17-av31 LF#11.0987 2.Hj-Angebot-2025/2026,bs17-av31 LF#05.0987 2.Hj-Angebot-2025/2026,bs17-av31 LF#06.0987 2.Hj-Angebot-2025/2026;;zoe.blome@bs17.hamburg.de;zoe.blome@bs17.hamburg.de;Aktiv;Lernende;DiViS;37a83068-435f-4703-9223-7bb1a2b2d3f6;000000068242;01.08.2023;31.01.2026;31.07.2026
Moritz;Bergner;;28.11.2003;Keine Angabe;;bs17;bs17;bs17-mk35-Klasse-2024/2025;mk35;bs17-mk35 LF#07.0995 2.Hj-Angebot-2025/2026,bs17-mk35 LF#05.0995 2.Hj-Angebot-2025/2026,bs17-mk35 LF#11.0995 2.Hj-Angebot-2025/2026,bs17-mk35 FachE 2.Hj-Angebot-2025/2026,bs17-mk35 LF#09.0995 2.Hj-Angebot-2025/2026,bs17-mk35 LF#08.0995 2.Hj-Angebot-2025/2026,bs17-mk35 WuG 2.Hj-Angebot-2025/2026;;moritz.bergner@bs17.hamburg.de;moritz.bergner@bs17.hamburg.de;Aktiv;Lernende;DiViS;3a83ae37-6df9-47da-8ed3-410b14da6fc2;000000016394;01.08.2023;31.01.2026;31.07.2026
Enno;Bluhm;;12.10.2005;Keine Angabe;;bs17;bs17;bs17-sc31-Klasse-2024/2025;sc31;bs17-sc31 Praktikum-Angebot-2024/2025;;enno.bluhm@bs17.hamburg.de;enno.bluhm@bs17.hamburg.de;Aktiv;Lernende;DiViS;45b3b154-88be-4635-94f1-3651c0cdc74b;000000014263;01.08.2023;31.07.2025;28.01.2026
Thea;Blömer;;15.07.2001;Keine Angabe;;bs17;bs17;bs17-mf31-Klasse-2024/2025;mf31;bs17-mf31 MFViP 2.Hj-Angebot-2025/2026,bs17-mf31 LF#11.1024 2.Hj-Angebot-2025/2026,bs17-mf31 WuG 2.Hj-Angebot-2025/2026,bs17-mf31 LF#02.1024 2.Hj-Angebot-2025/2026,bs17-mf31 LF#10.1024 2.Hj-Angebot-2025/2026,bs17-mf31 LF#04.1024 2.Hj-Angebot-2025/2026,bs17-mf31 LF#06.1024 2.Hj-Angebot-2025/2026,bs17-mf31 SuK 2.Hj-Angebot-2025/2026,bs17-mf31 FachE 2.Hj-Angebot-2025/2026,bs17-mf31 LF#08.1024 2.Hj-Angebot-2025/2026,bs17-mf31 LF#05.1024 2.Hj-Angebot-2025/2026,bs17-mf31 LF#09.1024 2.Hj-Angebot-2025/2026,bs17-mf31 LF#07.1024 2.Hj-Angebot-2025/2026;;thea.bloemer@bs17.hamburg.de;thea.bloemer@bs17.hamburg.de;Aktiv;Lernende;DiViS;46a0a3ee-3204-4be7-bdd0-7633275169ee;000000052864;01.08.2023;31.07.2026;28.01.2027
Simon;Bergmayr;;29.08.2001;Keine Angabe;;bs17;bs17;bs17-fos50-Klasse-2025/2026;fos50;;;simon.bergmayr@bs17.hamburg.de;simon.bergmayr@bs17.hamburg.de;Inaktiv;Lernende;DiViS;49328074-e133-499e-b78d-37fd009dbc82;000000011768;01.08.2025;31.07.2026;28.01.2027
Ali Ahmad;Binesh;;25.08.2002;Keine Angabe;;bs17;bs17;bs17-sc31-Klasse-2024/2025;sc31;bs17-sc31 Praktikum-Angebot-2024/2025;;ali.binesh@bs17.hamburg.de;ali.binesh@bs17.hamburg.de;Aktiv;Lernende;DiViS;511607f8-67f3-4aa1-9c47-fb2496b7d437;000000014274;01.08.2023;31.07.2025;28.01.2026
Anastasia Maria;Berger;;18.01.2002;Keine Angabe;;bs17;bs17;bs17-mk41-Klasse-2024/2025;mk41;;;anastasia.berger@bs17.hamburg.de;anastasia.berger@bs17.hamburg.de;Aktiv;Lernende;DiViS;68ad3acf-bce0-4871-894c-5eed4a3b0f02;000000017479;01.02.2024;31.01.2026;31.07.2026
Albert;Blum;;05.02.2004;Keine Angabe;;bs17;bs17;bs17-meg34-Klasse-2024/2025;meg34;;;albert.blum@bs17.hamburg.de;albert.blum@bs17.hamburg.de;Aktiv;Lernende;DiViS;8d285d8f-7813-41ab-8586-2d6636a1b13f;000000016728;01.08.2023;31.07.2026;28.01.2027
Thomas;Bissinger;;15.01.1976;Keine Angabe;Bi;bs17;bs17;bs17-meg34-Klasse-2024/2025,bs17-meg21-Klasse-2024/2025;meg34,meg21;bs17-meg21  |  LF 11c-Angebot-2024/2025,bs17-meg21  |  LF 08-Angebot-2024/2025,bs17-meg21  |  LF 12d-Angebot-2024/2025,bs17-meg21  |  LF 13d-Angebot-2024/2025,bs17-meg21 LF#09.0685 1.Hj-Angebot-2024/2025;;thomas.bissinger@bs17.hamburg.de;thomas.bissinger@bs17.hamburg.de;Aktiv;Lehrkraft;DiViS;9b7cf560-f35f-42fc-a657-9ccd00bcb28f;000000024144;01.11.2006;31.12.2099;30.06.2100
Alen;Bigdeli;;28.04.2006;Keine Angabe;;bs17;bs17;bs17-sc33-Klasse-2024/2025;sc33;bs17-sc33 Praktikum-Angebot-2024/2025;;alen.bigdeli@bs17.hamburg.de;alen.bigdeli@bs17.hamburg.de;Aktiv;Lernende;DiViS;9c831544-7d02-4d01-b39a-47fecb8c83b5;000000014258;01.08.2023;31.07.2025;28.01.2026
Raya;Berner;;09.05.2008;Keine Angabe;;bs17;bs17;bs17-mwp41-Klasse-2024/2025;mwp41;;;raya.berner@bs17.hamburg.de;raya.berner@bs17.hamburg.de;Aktiv;Lernende;DiViS;a641e63b-9f11-450f-99c2-dbac078ec966;000000019448;01.08.2024;31.07.2026;28.01.2027
Firat;Biyikli;;12.08.2007;Keine Angabe;;bs17;bs17;bs17-web43-Klasse-2024/2025;web43;bs17-web43 LF#04.1674 2.Hj-Angebot-2025/2026,bs17-web43 LF#03.1674 2.Hj-Angebot-2025/2026,bs17-web43 SuK 2.Hj-Angebot-2025/2026,bs17-web43 LF#06.1674 2.Hj-Angebot-2025/2026,bs17-web43 WuG 2.Hj-Angebot-2025/2026,bs17-web43 - LF 07 - Fachenglisch - 2. HJ-Angebot-2025/2026,bs17-web43 LF#05.1674 2.Hj-Angebot-2025/2026;;firat.biyikli@bs17.hamburg.de;firat.biyikli@bs17.hamburg.de;Aktiv;Lernende;DiViS;ac381fb8-dca2-40f7-af80-b9b99fef9005;000000017903;01.08.2024;31.07.2026;28.01.2027
Lennart;Blumenhagen;;03.08.2003;Keine Angabe;;bs17;bs17;bs17-mk42-Klasse-2024/2025;mk42;bs17-mk42 LF#03 Beschaffung-Angebot-2025/2026,bs17-mk42 LF#06 Marketingprojekt-Angebot-2025/2026,bs17-mk4 SuK Ha 1.+2.Hj-Angebot-2025/2026,bs17-mk42 LF#01 Ausbildungsbetrieb-Angebot-2025/2026,bs17-mk42 LF#04 Märkte analysieren-Angebot-2025/2026,bs17-mk42 LF#02 Rewe-Angebot-2025/2026,bs17-MK4 1.+2. Hj Video-Angebot-2025/2026,bs17-mk42 LF#07 K-Instrumente I-Angebot-2025/2026;;lennart.blumenhagen@bs17.hamburg.de;lennart.blumenhagen@bs17.hamburg.de;Aktiv;Lernende;DiViS;bb6a4ad1-68a8-483a-9609-b30b51458f92;000000019597;01.08.2024;31.07.2026;28.01.2027
Zelin;Bindal;;30.05.2001;Keine Angabe;;bs17;bs17;;;;;zelin.bindal@bs17.hamburg.de;zelin.bindal@bs17.hamburg.de;Inaktiv;Lernende;DiViS;d2d3faf2-8013-402e-b6c0-22564202bb60;000000058658;01.02.2025;30.04.2025;28.10.2025
Diana Manuela;Bobuescu;;26.01.2005;Keine Angabe;;bs17;bs17;bs17-web50-Klasse-2025/2026;web50;;;diana.bobuescu@bs17.hamburg.de;diana.bobuescu@bs17.hamburg.de;Inaktiv;Lernende;DiViS;f1c57267-91d9-4633-99ed-aefd77c027cd;000000041276;01.08.2025;31.07.2027;28.01.2028
Jona;Bernardy;;18.10.2003;Keine Angabe;;bs17;bs17;bs17-kas50-Klasse-2025/2026;kas50;;;jona.bernardy@bs17.hamburg.de;jona.bernardy@bs17.hamburg.de;Inaktiv;Lernende;DiViS;f27b7e90-f977-4d76-b904-b1125a5309ad;000000022545;01.08.2025;31.07.2027;28.01.2028
Emma;Biller;;13.03.1999;Keine Angabe;;bs17;bs17;bs17-meg50-Klasse-2025/2026;meg50;;;emma.biller@bs17.hamburg.de;emma.biller@bs17.hamburg.de;Inaktiv;Lernende;DiViS;fd87a0ba-0b19-4d0e-ad68-bf4378b18823;000000023627;01.08.2025;31.07.2028;28.01.2029
Thomas;Bissinger-Admin;;01.01.1900;Keine Angabe;;bs17;bs17;;;;;thomas.bissinger-admin@bs17.hamburg.de;thomas.bissinger-admin@bs17.hamburg.de;Aktiv;Schuladmin;Manuell;fe7262a3-4d93-4293-9e4c-88a42edb7fcc;000000140334;05.02.2025;31.12.2099;30.06.2100
+;Al-Sharqawi Salah Ahmad Ibrahim;;12.07.2007;Keine Angabe;;bs17;bs17;bs17-va42-Klasse-2024/2025;va42;;;+.al-sharqawisalahahmadibrahim@bs17.hamburg.de;+.al-sharqawisalahahmadibrahim@bs17.hamburg.de;Aktiv;Lernende;DiViS;41d510e7-2f8e-4ce8-b357-d4dc683f32a1;000000021075;01.08.2024;31.07.2025;28.01.2026
Aminata;Cissé;;30.12.2005;Keine Angabe;;bs17;bs17;bs17-mk42-Klasse-2024/2025;mk42;bs17-mk42 LF#03 Beschaffung-Angebot-2025/2026,bs17-mk42 LF#06 Marketingprojekt-Angebot-2025/2026,bs17-mk4 SuK Sl 1.+2.Hj-Angebot-2025/2026,bs17-MK4 Ag Foto_KI 1.+2. Hj.-Angebot-2025/2026,bs17-mk42 LF#01 Ausbildungsbetrieb-Angebot-2025/2026,bs17-mk42 LF#04 Märkte analysieren-Angebot-2025/2026,bs17-mk42 LF#02 Rewe-Angebot-2025/2026,bs17-mk42 LF#07 K-Instrumente I-Angebot-2025/2026;;aminata.cisse@bs17.hamburg.de;aminata.cisse@bs17.hamburg.de;Aktiv;Lernende;DiViS;ff077981-7b63-4d07-8697-f57e7a3953b7;000000019987;01.08.2024;31.07.2026;28.01.2027
Ayca;Göv;;01.04.2003;Keine Angabe;;bs17;bs17;bs17-kd31-Klasse-2024/2025;kd31;;;ayca.goev@bs17.hamburg.de;ayca.goev@bs17.hamburg.de;Aktiv;Lernende;DiViS;53a1f15a-83bd-4fbe-b436-55b22a271ad5;000000044177;01.08.2020;31.07.2026;28.01.2027
Balthasar;Gieß;;21.03.2002;Keine Angabe;;bs17;bs17;bs17-meg32-Klasse-2024/2025;meg32;;;balthasar.giess@bs17.hamburg.de;balthasar.giess@bs17.hamburg.de;Aktiv;Lernende;DiViS;b01d5af3-53cb-4265-863a-52afcc25669a;000000016737;01.08.2023;31.01.2026;31.07.2026
Lena Maria;Andrée;;09.12.2004;Keine Angabe;;bs17;bs17;bs17-mk41-Klasse-2024/2025;mk41;;;lena.andree@bs17.hamburg.de;lena.andree@bs17.hamburg.de;Aktiv;Lernende;DiViS;5f810eda-4f4f-448a-8966-0ed612771809;000000064949;01.08.2021;31.07.2027;28.01.2028
Ecedèo;De Oliveira Pereira;;21.07.2002;Keine Angabe;;bs17;bs17;bs17-mk34-Klasse-2024/2025;mk34;bs17-mk34 FachE 2.Hj-Angebot-2025/2026,bs17-mk34 LF#09.0995 2.Hj-Angebot-2025/2026,bs17-mk34 LF#08.0995 2.Hj-Angebot-2025/2026,bs17-mk34 LF#11.0995 2.Hj-Angebot-2025/2026,bs17-mk34 WuG 2.Hj-Angebot-2025/2026,bs17-mk34 LF#05.0995 2.Hj-Angebot-2025/2026,bs17-mk34 LF#07.0995 2.Hj-Angebot-2025/2026;;ecedeo.deoliveirapereira@bs17.hamburg.de;ecedeo.deoliveirapereira@bs17.hamburg.de;Aktiv;Lernende;DiViS;a833b13a-64d3-476e-a509-328eede88285;000000016819;01.08.2023;31.01.2026;31.07.2026
Anke Bettina;Klein;;14.07.1967;Keine Angabe;Kl;bs17;bs17;;;;;anke.klein@bs17.hamburg.de;anke.klein@bs17.hamburg.de;Aktiv;Lehrkraft;DiViS;64489128-4b56-45ce-8b6c-aafeef6fd8cf;000000021760;01.05.2011;31.12.2099;30.06.2100
CSV
);
        $node = Tinebase_FileSystem::getInstance()->stat($path . '/import.csv');

        $oldValue = Tinebase_Config::getInstance()->{Tinebase_Config::ACCOUNT_TWIG_LOGIN};
        $raii = new Tinebase_RAII(function() use($oldValue) {
            $oldValue ? Tinebase_Config::getInstance()->{Tinebase_Config::ACCOUNT_TWIG_LOGIN} = $oldValue :
                Tinebase_Config::getInstance()->delete(Tinebase_Config::ACCOUNT_TWIG_LOGIN);
            Setup_Controller::getInstance()->clearCacheDir();
        });
        Tinebase_Config::getInstance()->{Tinebase_Config::ACCOUNT_TWIG_LOGIN} = '{{ account.accountFirstName|transliterate|removeSpace|trim[0:1]|lower }}{{ account.accountLastName|transliterate|removeSpace|lower }}';
        Setup_Controller::getInstance()->clearCacheDir();

        $importer = new Courses_Import_DivisCourses([
            'divisFile' => '/shared/unittest/import.csv',
            'teacherPwdFile' => '/shared/unittest/teacherPwdExport.docx',
        ]);
        $importer->import();

        $updatedNode = Tinebase_FileSystem::getInstance()->get($node->getId());
        $this->assertSame($node->revision, $updatedNode->revision);

        $notes = Tinebase_Notes::getInstance()->getNotesOfRecord(Tinebase_Model_Tree_Node::class, $node->getId());
        $this->assertSame(2, $notes->count());
        $note = $notes->find(fn(Tinebase_Model_Note $note) => strpos($note->note, 'last imported revision: ' . $node->revision) === 0, null);
        $this->assertNotNull($note, print_r($notes->toArray(), true));
        $this->assertStringContainsString(
            'import succeeded' . PHP_EOL .
            'created course: bos32' . PHP_EOL .
            'created course: av31' . PHP_EOL .
            'created course: avm31' . PHP_EOL .
            'created course: avm33' . PHP_EOL .
            'created course: mk44' . PHP_EOL .
            'created course: mk35' . PHP_EOL .
            'created course: sc31' . PHP_EOL .
            'created course: mf31' . PHP_EOL .
            'created course: mk41' . PHP_EOL, $note->note);
        $this->assertStringContainsString(
            'create teacher account: ', $note->note);
        foreach (['t.bissinger', 'a.klein'] as $teacher) {
            $this->assertStringContainsString(
                $teacher . PHP_EOL, $note->note);
        }
        $this->assertStringContainsString(
            'create student account: bos32-b.boateng' . PHP_EOL .
            'create student account: av31-l.birker' . PHP_EOL .
            'create student account: avm31-k.betanay' . PHP_EOL .
            'create student account: avm33-d.biriukov' . PHP_EOL .
            'create student account: mk44-a.bloh' . PHP_EOL .
            'create student account: avm33-y.bieliakov' . PHP_EOL .
            'create student account: av31-z.blome' . PHP_EOL .
            'create student account: mk35-m.bergner' . PHP_EOL .
            'create student account: sc31-e.bluhm' . PHP_EOL .
            'create student account: mf31-t.bloemer' . PHP_EOL .
            'create student account: sc31-a.binesh' . PHP_EOL .
            'create student account: mk41-a.berger' . PHP_EOL .
            'create student account: meg34-a.blum' . PHP_EOL .
            'create student account: sc33-a.bigdeli' . PHP_EOL .
            'create student account: mwp41-r.berner' . PHP_EOL .
            'create student account: web43-f.biyikli' . PHP_EOL .
            'create student account: mk42-l.blumenhagen' . PHP_EOL .
            'create student account: va42-a.al-sharqawisalahahmadibrahim' . PHP_EOL .
            'create student account: mk42-a.cisse' . PHP_EOL .
            'create student account: kd31-a.goev' . PHP_EOL .
            'create student account: meg32-b.giess' . PHP_EOL .
            'create student account: mk41-l.andree' . PHP_EOL .
            'create student account: mk34-e.deoliveirapereira', $note->note);

        $teacherPwdNod = Tinebase_FileSystem::getInstance()->stat($path . '/teacherPwdExport.docx');
        $teacherPwdExport = $this->getPlainTextFromDocx(Tinebase_FileSystem::getInstance()->getRealPathForHash($teacherPwdNod->hash));
        $this->assertStringContainsString('a.klein', $teacherPwdExport);
        try {
            $sz = Tinebase_User::getInstance()->getUserByLoginName('a.klein', Tinebase_Model_FullUser::class);
            $this->assertStringContainsString($sz->xprops()['autoGenPwd'], $teacherPwdExport);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // TODO make this work if teacher loginname also has "class" prefix
        }
        $this->assertStringContainsString('t.bissinger', $teacherPwdExport);

        $bos32 = Courses_Controller_Course::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Courses_Model_Course::class, [
                ['field' => 'name', 'operator' => 'equals', 'value' => 'bos32'],
            ]))->getFirstRecord();
        $this->assertNotNull($bos32);
        $attachments = Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachments($bos32);
        $this->assertSame(1, $attachments->count());
        $studentPwdExport = $this->getPlainTextFromDocx(Tinebase_FileSystem::getInstance()->getRealPathForHash($attachments->getFirstRecord()->hash));
        $this->assertSame(1, preg_match('/Benisa BenBoatengBenutzer: bos32-b.boatengPasswort: (.*)E-Mail:bos32-b.boateng/', $studentPwdExport, $m), $studentPwdExport);
        $bboatengPwd = $m[1];

        $student = Tinebase_User::getInstance()->getUserByLoginName('bos32-b.boateng');
        file_put_contents('tine20://' . $path . '/import.csv',
            <<<CSV
Vorname;Nachname;Rufname;Geburtstag;Geschlecht;Kürzel;Schulen;Stammschule;Klassen;Klassennamen;Angebote;Manuelle Gruppen;Anmeldekennung;E-Mail-Adressen der weiteren Schulen;Status;Rolle;Quelle;Interne ID;Kurze ID;Gültig ab;Gültig bis;Löschdatum
Benisa Ben;Boteng;;10.09.2002;Keine Angabe;;bs17;bs17;bs17-bos32-Klasse-2024/2025;bos32;;;benisa.boteng@bs17.hamburg.de;benisa.boteng@bs17.hamburg.de;Aktiv;Lernende;DiViS;0580ed78-3bcd-4af6-9226-f0057d19ea77;000000019863;01.08.2024;31.07.2025;28.01.2026
CSV
        );

        $node = Tinebase_FileSystem::getInstance()->stat($path . '/import.csv');
        $this->assertSame((int)$updatedNode->revision + 1, (int)$node->revision);

        $importer = new Courses_Import_DivisCourses([
            'divisFile' => '/shared/unittest/import.csv',
            'teacherPwdFile' => '/shared/unittest/teacherPwdExport.docx',
        ]);
        $importer->import();

        $updatedNode = Tinebase_FileSystem::getInstance()->get($node->getId());
        $this->assertSame($node->revision, $updatedNode->revision);

        $notes = Tinebase_Notes::getInstance()->getNotesOfRecord(Tinebase_Model_Tree_Node::class, $node->getId());
        $this->assertSame(3, $notes->count());
        $note = $notes->find(fn(Tinebase_Model_Note $note) => strpos($note->note, 'old: last imported revision: ' . ($node->revision - 1)) === 0, null);
        $this->assertNotNull($note, print_r($notes->toArray(), true));
        /** @var Tinebase_Model_Note $note */
        $note = $notes->find(fn(Tinebase_Model_Note $note) => strpos($note->note, 'last imported revision: ' . $node->revision) === 0, null);
        $this->assertNotNull($note, print_r($notes->toArray(), true));

        $this->assertStringContainsString(
            'import succeeded' . PHP_EOL .
            'no new courses to create' . PHP_EOL .
            'rename student Benisa Ben Boateng to Benisa Ben Boteng' . PHP_EOL .
            'expiring student ', $note->note);
        $updatedStudent = Tinebase_User::getInstance()->getUserByLoginName('bos32-b.boteng');
        $this->assertSame($student->getId(), $updatedStudent->getId());

        $attachments = Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachments($bos32);
        $this->assertSame(2, $attachments->count());
        $attachment = $attachments->filter(function(Tinebase_Model_Tree_Node $node) {
            return strpos($node->name, '(') !== false;
        })->getFirstRecord();
        $this->assertNotNull($attachment);
        $studentPwdExport = $this->getPlainTextFromDocx(Tinebase_FileSystem::getInstance()->getRealPathForHash($attachment->hash));
        $this->assertSame(1, preg_match('/Benisa BenBotengBenutzer: bos32-b.botengPasswort: (.*)E-Mail:bos32-b.boteng/', $studentPwdExport, $m));
        $this->assertSame($bboatengPwd, $m[1]);

        file_put_contents('tine20://' . $path . '/import.csv',
            <<<CSV
Vorname;Nachname;Rufname;Geburtstag;Geschlecht;Kürzel;Schulen;Stammschule;Klassen;Klassennamen;Angebote;Manuelle Gruppen;Anmeldekennung;E-Mail-Adressen der weiteren Schulen;Status;Rolle;Quelle;Interne ID;Kurze ID;Gültig ab;Gültig bis;Löschdatum
Benisa Ben;Boteng;;10.09.2002;Keine Angabe;;bs17;bs17;bs17-av31-Klasse-2024/2025;av31;;;benisa.boteng@bs17.hamburg.de;benisa.boteng@bs17.hamburg.de;Aktiv;Lernende;DiViS;0580ed78-3bcd-4af6-9226-f0057d19ea77;000000019863;01.08.2024;31.07.2025;28.01.2026
CSV
        );

        $node = Tinebase_FileSystem::getInstance()->stat($path . '/import.csv');
        $this->assertSame((int)$updatedNode->revision + 1, (int)$node->revision);

        $importer = new Courses_Import_DivisCourses([
            'divisFile' => '/shared/unittest/import.csv',
            'teacherPwdFile' => '/shared/unittest/teacherPwdExport.docx',
        ]);
        $importer->import();

        $updatedNode = Tinebase_FileSystem::getInstance()->get($node->getId());
        $this->assertSame($node->revision, $updatedNode->revision);

        $notes = Tinebase_Notes::getInstance()->getNotesOfRecord(Tinebase_Model_Tree_Node::class, $node->getId());
        $this->assertSame(4, $notes->count());
        /** @var Tinebase_Model_Note $note */
        $note = $notes->find(fn(Tinebase_Model_Note $note) => strpos($note->note, 'last imported revision: ' . $node->revision) === 0, null);
        $this->assertNotNull($note, print_r($notes->toArray(), true));
        $this->assertStringContainsString(
            'import succeeded' . PHP_EOL .
            'no new courses to create' . PHP_EOL .
            'remove student: bos32-b.boteng from course: bos32' . PHP_EOL .
            'add student: bos32-b.boteng to course: av31', $note->note);

        $student = Tinebase_User::getInstance()->getUserByLoginName('av31-b.boteng');
        self::assertEquals('av31-b.boteng@mail.test', $student->accountEmailAddress);
        $mailAccount = Admin_Controller_EmailAccount::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Account::class, [
                ['field' => 'user_id', 'operator' => 'equals', 'value' => $student->getId()],
            ]))->getFirstRecord();
        self::assertNotNull($mailAccount);
        self::assertEquals('av31-b.boteng@mail.test', $mailAccount->name);

        unset($raii);
    }
}
