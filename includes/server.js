const express = require("express");
const http = require("http");
const socketIo = require("socket.io");
const cors = require("cors");

const app = express();
app.use(cors());
app.use(express.json());

const server = http.createServer(app);

const io = socketIo(server, {
    cors: { origin: "*" }
});

// user joins personal room
io.on("connection", (socket) => {

    socket.on("join", (userId) => {
        socket.join("user_" + userId);
    });

    // NOTIFICATIONS
    socket.on("notify", (data) => {
        io.to("user_" + data.userId).emit("new_notification", data);
    });

    // CHAT ALERTS
    socket.on("chat_alert", (data) => {
        io.to("user_" + data.userId).emit("new_message", data);
    });

    // CART ALERTS
    socket.on("cart_update", (data) => {
        io.to("user_" + data.userId).emit("cart_changed", data);
    });
});

server.listen(3000, () => {
    console.log("BookMart realtime running on 3000");
});