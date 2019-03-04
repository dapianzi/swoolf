var my_utils = {
    storage: {
        get: function (name) {
            return JSON.parse(localStorage.getItem(name))
        },
        set: function (name, val) {
            localStorage.setItem(name, JSON.stringify(val))
        },
        add: function (name, addVal) {
            let oldVal = this.get(name)
            let newVal = oldVal.concat(addVal)
            this.set(name, newVal)
        }
    },
};