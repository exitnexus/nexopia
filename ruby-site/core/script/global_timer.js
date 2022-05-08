GlobalTimer = {
	callbacks: {},
	counter: 0,
	setTimeout: function(func, delay, obj, overrideScope) {
		this.callbacks[this.counter] = new Callback(func, obj, overrideScope);
		window.setTimeout("GlobalTimer.callbacks[" + this.counter + "].execute()", delay);
		this.counter++;
	}
}

Callback = function(func, obj, overrideScope) {
	this.func = func;
	this.obj = obj;
	this.overrideScope = overrideScope;
}

Callback.prototype = {
	execute: function() {
		if (this.overrideScope) {
			this.obj.__timer_func = this.func;
			this.obj.__timer_func(this.obj);
			delete this.obj["__timer_func"];	
		} else {
			this.func(this.obj);
		}
	}
};
