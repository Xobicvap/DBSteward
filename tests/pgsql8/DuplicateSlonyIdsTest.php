<?php
/**
 * Tests that slonyId attributes are correctly checked during both build and upgrade
 *
 * @package DBSteward
 * @license http://www.opensource.org/licenses/bsd-license.php Simplified BSD License
 * @author Austin Hyde <austin109@gmail.com>
 */

require_once 'PHPUnit/Framework/TestCase.php';

require_once __DIR__ . '/../dbstewardUnitTestBase.php';

class DuplicateSlonyIdsTest extends dbstewardUnitTestBase {

  public function testDuplicateTableIds() {
    $xml = <<<XML
<table name="a" owner="ROLE_OWNER" slonyId="1">
  <column name="b" type="serial" slonyId="1"/>
</table>
<table name="c" owner="ROLE_OWNER" slonyId="1">
  <column name="d" type="serial" slonyId="2"/>
</table>
XML;
    $this->common_dups($xml, "table slonyId 1 already in table_slony_ids -- duplicates not allowed");
  }

  public function testDuplicateColumnIds() {
    $xml = <<<XML
<table name="a" owner="ROLE_OWNER" slonyId="1">
  <column name="b" type="serial" slonyId="1"/>
</table>
<table name="c" owner="ROLE_OWNER" slonyId="2">
  <column name="d" type="serial" slonyId="1"/>
</table>
XML;
    $this->common_dups($xml, "column sequence slonyId 1 already in sequence_slony_ids -- duplicates not allowed");
  }

  public function testDuplicateSequenceIds() {
    $xml = <<<XML
<sequence name="a" owner="ROLE_OWNER" slonyId="1"/>
<sequence name="b" owner="ROLE_OWNER" slonyId="1"/>
XML;
    $this->common_dups($xml, "sequence slonyId 1 already in sequence_slony_ids -- duplicates not allowed");
  }

  public function testDuplicateSequenceAndColumnIds() {
    $xml = <<<XML
<table name="a" owner="ROLE_OWNER" slonyId="1">
  <column name="b" type="serial" slonyId="1"/>
</table>
<sequence name="c" owner="ROLE_OWNER" slonyId="1"/>
XML;
    $this->common_dups($xml, "sequence slonyId 1 already in sequence_slony_ids -- duplicates not allowed");
  }

  public function testDifferentTableIdsBetweenVersions() {
    $old = <<<XML
<table name="a" owner="ROLE_OWNER" slonyId="1">
  <column name="b" type="serial" slonyId="1"/>
</table>
XML;
    $new = <<<XML
<table name="a" owner="ROLE_OWNER" slonyId="2">
  <column name="b" type="serial" slonyId="1"/>
</table>
XML;

    $this->common_mismatch($old, $new, "table slonyId 2 in new does not match slonyId 1 in old");
  }

  public function testDifferentColumnIdsBetweenVersions() {
    $old = <<<XML
<table name="a" owner="ROLE_OWNER" slonyId="1">
  <column name="b" type="serial" slonyId="1"/>
</table>
XML;
    $new = <<<XML
<table name="a" owner="ROLE_OWNER" slonyId="1">
  <column name="b" type="serial" slonyId="2"/>
</table>
XML;

    $this->common_mismatch($old, $new, "column sequence slonyId 2 in new does not match slonyId 1 in old");
  }

  public function testDifferentSequenceIdsBetweenVersions() {
    $old = <<<XML
<sequence name="a" owner="ROLE_OWNER" slonyId="1"/>
XML;
    $new = <<<XML
<sequence name="a" owner="ROLE_OWNER" slonyId="2"/>
XML;

    $this->common_mismatch($old, $new, "sequence slonyId 2 in new does not match slonyId 1 in old");
  }

  public function testChangeIgnoreRequiredTableSlonyIdAllowed() {
    $old = <<<XML
<table name="a" owner="ROLE_OWNER" slonyId="IGNORE_REQUIRED">
  <column name="b" type="serial" slonyId="1"/>
</table>
XML;
    $new = <<<XML
<table name="a" owner="ROLE_OWNER" slonyId="1">
  <column name="b" type="serial" slonyId="1"/>
</table>
XML;

    $this->common_mismatch($old, $new, false);
    $this->common_mismatch($new, $old, false);
  }

  public function testChangeIgnoreRequiredColumnSlonyId() {
    $old = <<<XML
<table name="a" owner="ROLE_OWNER" slonyId="1">
  <column name="b" type="serial" slonyId="IGNORE_REQUIRED"/>
</table>
XML;
    $new = <<<XML
<table name="a" owner="ROLE_OWNER" slonyId="1">
  <column name="b" type="serial" slonyId="1"/>
</table>
XML;

    $this->common_mismatch($old, $new, false);
    $this->common_mismatch($new, $old, false);
  }

  public function testChangeIgnoreRequiredSequenceSlonyId() {
    $old = <<<XML
<sequence name="a" owner="ROLE_OWNER" slonyId="IGNORE_REQUIRED"/>
XML;
    $new = <<<XML
<sequence name="a" owner="ROLE_OWNER" slonyId="1"/>
XML;

    $this->common_mismatch($old, $new, false);
    $this->common_mismatch($new, $old, false);
  }

  public function testMismatchedSequenceAndColumnIdsBetweenVersions() {
    $old = <<<XML
<table name="a" owner="ROLE_OWNER" slonyId="1">
  <column name="b" type="serial" slonyId="1"/>
</table>
<sequence name="c" owner="ROLE_OWNER" slonyId="2"/>
XML;
    $new = <<<XML
<table name="a" owner="ROLE_OWNER" slonyId="1">
  <column name="b" type="serial" slonyId="2"/>
</table>
<sequence name="c" owner="ROLE_OWNER" slonyId="1"/>
XML;

    $this->common_mismatch($old, $new, "column sequence slonyId 2 in new does not match slonyId 1 in old");
  }

  private function common_dups($xml, $expected) {
    $docxml = <<<XML
<dbsteward>
  <database>
    <host>db-host</host>
    <name>dbsteward</name>
    <role>
      <application>dbsteward_phpunit_app</application>
      <owner>deployment</owner>
      <replication/>
      <readonly/>
    </role>
    <slony>
      <masterNode id="1"/>
      <replicaNode id="2" providerId="1"/>
      <replicaNode id="3" providerId="2"/>
      <replicationSet id="1"/>
      <replicationUpgradeSet id="2"/>
    </slony>
    <configurationParameter name="TIME ZONE" value="America/New_York"/>
  </database>
  <schema name="dbsteward" owner="ROLE_OWNER">
    $xml
  </schema>
</dbsteward>
XML;

    $doc = new SimpleXMLElement($docxml);

    pgsql8::$table_slony_ids = array();
    pgsql8::$sequence_slony_ids = array();
    pgsql8::$known_pg_identifiers = array();

    // just build
    if ($expected !== false) {
      $this->expect_exception($expected, function() use($doc) {
        pgsql8::build_slonik($doc, "php://memory");
      });
    }
    else {
      $this->expect_no_exception(function() use($doc) {
        pgsql8::build_slonik($doc, "php://memory");
      });
    }

    pgsql8::$table_slony_ids = array();
    pgsql8::$sequence_slony_ids = array();
    pgsql8::$known_pg_identifiers = array();

    // just upgrade
    if ($expected !== false) {
      $this->expect_exception($expected, function() use($doc) {
        pgsql8::build_upgrade_slonik($doc, $doc, __DIR__.'/../testdata/slonyid_test');
      });
    }
    else {
      $this->expect_no_exception(function() use($doc) {
        pgsql8::build_upgrade_slonik($doc, $doc, __DIR__.'/../testdata/slonyid_test');
      });
    }
  }

  private function common_mismatch($a, $b, $expected) {
    $docxml = <<<XML
<dbsteward>
  <database>
    <host>db-host</host>
    <name>dbsteward</name>
    <role>
      <application>dbsteward_phpunit_app</application>
      <owner>deployment</owner>
      <replication/>
      <readonly/>
    </role>
    <slony>
      <masterNode id="1"/>
      <replicaNode id="2" providerId="1"/>
      <replicaNode id="3" providerId="2"/>
      <replicationSet id="1"/>
      <replicationUpgradeSet id="2"/>
    </slony>
    <configurationParameter name="TIME ZONE" value="America/New_York"/>
  </database>
  <schema name="dbsteward" owner="ROLE_OWNER">
XML;

    $adoc = new SimpleXMLElement($docxml.$a."</schema></dbsteward>");
    $bdoc = new SimpleXMLElement($docxml.$b."</schema></dbsteward>");

    pgsql8::$table_slony_ids = array();
    pgsql8::$sequence_slony_ids = array();
    pgsql8::$known_pg_identifiers = array();

    if ($expected !== false) {
      $this->expect_exception($expected, function() use($adoc, $bdoc) {
        pgsql8::build_upgrade_slonik($adoc, $bdoc, __DIR__.'/../testdata/slonyid_test');
      });
    }
    else {
      $this->expect_no_exception(function() use($adoc, $bdoc) {
        pgsql8::build_upgrade_slonik($adoc, $bdoc, __DIR__.'/../testdata/slonyid_test');
      });
    }
  }

  private function expect_exception($expected, $f) {
    try {
      call_user_func($f);
    }
    catch (Exception $ex) {
      if (strcasecmp($ex->getMessage(), $expected) !== 0) {
        $this->fail("Expected exception with message '$expected', got '{$ex->getMessage()}'");
      } else {
        $this->assertTrue(true); // just evidence that we "asserted" something
        return;
      }
    }
    $this->fail("Expected exception with message '$expected', got nothing");
  }

  private function expect_no_exception($f) {
    try {
      call_user_func($f);
    }
    catch (Exception $ex) {
      $this->fail("Did not expect exception, got '{$ex->getMessage()}'");
    }
    $this->assertTrue(true); // evidence that we "asserted" something
  }
}