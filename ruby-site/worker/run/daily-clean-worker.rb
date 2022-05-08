lib_require :Worker, 'worker'

db = Worker::DeferredTask.db
# Delete tasks that expired more than a couple of days ago outright
db.query("DELETE FROM deferred_tasks WHERE expire < ?", Time.now.to_i - 2*24*60*60)
# Delete all tasks successfuly done up to now (actually up to an hour ago to prevent overlap)
db.query("DELETE FROM deferred_tasks WHERE expire < ? AND status = 'done'", Time.now.to_i - 60*60)