<?php
/**
 * @file
 * Contains \SiteAudit\Check\Users\RolesList.
 */

/**
 * Class SiteAuditCheckUsersRolesList.
 */
class SiteAuditCheckUsersRolesList extends SiteAuditCheckAbstract {

  /**
   * Implements \SiteAudit\Check\Abstract\getLabel().
   */
  public function getLabel() {
    return dt('List Roles');
  }

  /**
   * Implements \SiteAudit\Check\Abstract\getDescription().
   */
  public function getDescription() {
    return dt('Show all available roles and user counts.');
  }

  /**
   * Implements \SiteAudit\Check\Abstract\getResultFail().
   */
  public function getResultFail() {}

  /**
   * Implements \SiteAudit\Check\Abstract\getResultInfo().
   */
  public function getResultInfo() {
    $counts = array();
    foreach ($this->registry['roles'] as $name => $count_users) {
      $counts[] = "$name: $count_users";
    }
    return implode(', ', $counts);
  }

  /**
   * Implements \SiteAudit\Check\Abstract\getResultPass().
   */
  public function getResultPass() {}

  /**
   * Implements \SiteAudit\Check\Abstract\getResultWarn().
   */
  public function getResultWarn() {}

  /**
   * Implements \SiteAudit\Check\Abstract\getAction().
   */
  public function getAction() {}

  /**
   * Implements \SiteAudit\Check\Abstract\calculateScore().
   */
  public function calculateScore() {
    $sql_query = 'SELECT roles_target_id AS name';
    $sql_query .= ', COUNT(entity_id) AS count_users ';
    $sql_query .= 'FROM {user__roles} ';
    $sql_query .= 'GROUP BY name ';
    $sql_query .= 'ORDER BY name ASC ';
    $result = db_query($sql_query);
    foreach ($result as $row) {
      $this->registry['roles'][$row->name] = $row->count_users;
    }
    return SiteAuditCheckAbstract::AUDIT_CHECK_SCORE_INFO;
  }

}
