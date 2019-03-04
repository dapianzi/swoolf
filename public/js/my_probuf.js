var my_probuf = {
    root: null,
    ver: 1,
    writeBuf: function (msgid, buf) {
        var self = this;
        var length = buf.length;
        var buffer = new ArrayBuffer(buf.length + 4);
        var dv = new DataView(buffer);
        dv.setUint32(0, msgid, false);
        for (let i=0;i<buf.length;i++) {
            dv.setInt8(4+i, buf[i]);
        }
        return buffer;
    },
    readBuf: function (buf) {
        var dv = new DataView(buf);
        var msgid = dv.getUint32(0, false);
        var buf = new Uint8Array(buf, 4);
        return [msgid, buf];
    },
    Request_Message: function Request_Message(msg, req, callback) {
        var RequestMessage = this.root.lookupType("Proto."+req);
        var errMsg = RequestMessage.verify(msg);
        if (errMsg)
            throw new Error(errMsg);
        var message = RequestMessage.fromObject(msg); // or use .fromObject if conversion is necessary
        var buffer = RequestMessage.encode(message).finish();
        callback(buffer);
    },
    Response_Message: function (buf, res, callback) {
        var ResponseMessage = this.root.lookupType("Proto."+res);
        var message = ResponseMessage.decode(buf);
        var object = ResponseMessage.toObject(message, {
            longs: String,
            // enums: String,
            bytes: String,
        });
        callback(object);
    }
};