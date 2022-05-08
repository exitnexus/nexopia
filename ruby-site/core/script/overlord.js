Overlord = {
	jobs: [], //list of all the jobs we need taken care of
	assign: function(job) {
		//you can pass in either just a raw object that would be the constructor argument to Overlord.Job
		//or an Overlord.Job object
		if (!this.Job.prototype.isPrototypeOf(job)) {
			job = new this.Job(job);
		}
		this.jobs.push(job);
	},
	summonMinions: function(root) {
		//find the minions on the page and sort them by name
		var minionsList = this.findMinions(root);
		var minions = {};
		for (var i=0; i<minionsList.length; i++) {
			var minion = minionsList[i];
			var names = minion.getAttribute('minion_name');
			names = names.split(' ');
			for (var n=0; n<names.length; n++) {
				var name = names[n];
				minions[name] = minions[name] || [];
				minions[name].push(minion);
			}
		}

		//sort the jobs by priority
		this.jobs.sort(function(a, b) {return a.order - b.order;}); //lowest order happens first

		//assign jobs to their minions if their minions exist
		for (var j=0; j<this.jobs.length; j++) {
			var job = this.jobs[j];
			if (minions[job.minion]) {
				//assign each minion with the name job.minion the job
				for (var m=0; m<minions[job.minion].length; m++) {
					try {
						job.assign(minions[job.minion][m]);
					} catch (err) {
						if (console) {
							console.error("Overlord: Assignment of the job %o failed on the minion %o.", job, minions[job.minion][m]);
						}
						throw err;
					}
				}
			}
		}
	},
	findMinions: function(root) {
		root = [].concat(root);
		var found = [];
		for (var i=0; i<root.length; i++) {
			if (root[i] && Overlord.isMinion(root[i])) {
				found.push(root[i]); //getElementsBy doesn't include its root when it is searching
			}
			found = found.concat(YAHOO.util.Dom.getElementsBy(Overlord.isMinion, null, root[i]));
		}
		return found;
	},
	isMinion: function(element) {
		return element.getAttribute('minion_name');
	},
	toString: function() {
		var targetedNames = [];
		for (var j=0; j<this.jobs.length; j++) {
			targetedNames.push(this.jobs[j].minion);
		}
		return "Overlord<" + targetedNames.join(', ')+ ">";
		
	}
};

Overlord.Job = function(description) {
	this.tasks = {};
	for (task in description) {
		//Assume any task that is a function and we don't have a default 
		//for is an event to register.  The task click would contain a function 
		//for onclick.
		if (task == "priority") { //priority was used in ScriptManager, order should be used for Overlord
			if (console) {
				console.error(description);
			}
			throw "Attempted to use priority in an Overlord Job for " + description["minion"] + ".";
		} else if (this[task] === undefined && Function.prototype.isPrototypeOf(description[task])) {
			this.addTask(task, description[task]);
		} else {
			this[task] = description[task]; //override default properties
		}
	}
};

Overlord.Job.prototype = {
	minion: null, //the name of the minion this job should be assigned to
	load: null, //function called on load at page load or via ajax
	unload: null, //function called when the element is removed or unloaded
	scope: null, //optional object to execute in the scope of, if it is missing the minion will be used as a scope
	order: 0, //set this value to adjust the order the script is excuted in, lower numbers happen first
	tasks: null, //a map of event names to handling functions
	assign: function(minion) {
		var scope = this.scope || minion;
		if (this.load) {
			this.load.call(scope, minion);
		}
		for (task in this.tasks) {
			YAHOO.util.Event.on(minion, task, this.tasks[task], minion, scope);
		}
	},
	unassign: function(minion) {
		if (this.unload) {
			var scope = this.scope || minion;
			this.unload.call(scope, minion);
		}
	},
	addTask: function(name, task) {
		this.tasks[name] = task;
	}
};

YAHOO.util.Event.on(window, 'load', function() {Overlord.summonMinions();});
