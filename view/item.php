<?php
/* zKillboard
 * Copyright (C) 2012-2015 EVE-KILL Team and EVSCO.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!is_numeric($id))
{
    $id = Info::getItemId($id);
    if ($id > 0) header("Location: /item/$id/");
    else header("Location: /");
    die();
}

$info = Db::queryRow("select typeID, typeName, description from ccp_invTypes where typeID = :id", array(":id" => $id), 3600);
$info["description"] = str_replace("<br>", "\n", $info["description"]);
$info["description"] = strip_tags($info["description"]);
$hasKills = 1 == Db::queryField("select 1 as hasKills from zz_participants where shipTypeID = :id limit 1", "hasKills", array(":id" => $id), 3600);

$info["attributes"] = Db::query("SELECT categoryName, coalesce(displayName, attributeName) attributeName, coalesce(valueint,valuefloat) value  FROM ccp_invTypes JOIN ccp_dgmTypeAttributes ON (ccp_invTypes.typeid = ccp_dgmTypeAttributes.typeid) JOIN ccp_dgmAttributeTypes ON (ccp_dgmTypeAttributes.attributeid = ccp_dgmAttributeTypes.attributeid) LEFT JOIN ccp_dgmAttributeCategories ON (ccp_dgmAttributeTypes.categoryid=ccp_dgmAttributeCategories.categoryid) WHERE ccp_invTypes.typeid = :typeID and ccp_dgmAttributeCategories.categoryid is not null and displayName is not null and ccp_dgmAttributeTypes.categoryID not in (8,9) ORDER BY ccp_dgmAttributeCategories.categoryid,   ccp_dgmAttributeTypes.attributeid", array(":typeID" => $id));

$info["market"] = Db::query("select * from zz_item_price_lookup where typeID = :typeID order by priceDate desc limit 30", array(":typeID" => $id));

$app->render("item.html", array("info" => $info, "hasKills" => $hasKills));
