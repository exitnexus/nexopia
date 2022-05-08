begin

oc = 10000;
count = oc;

st = Time.now.to_f;
while (count -= 1) > 0
	$site.dbs[:masterdb].query("SHOW COLUMNS FROM accounts");
end
et = Time.now.to_f;

$log.info("Took #{et - st}s to run #{oc} queries (#{(et - st)/oc*1000}ms/query)");

rescue
$log.info "HWErfasjekfd"
end