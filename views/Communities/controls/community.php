<?php foreach ($results as $tab => $column) {
	if (empty($column["controls"])) {
		continue;
	}

	echo $column["controls"];
} ?>