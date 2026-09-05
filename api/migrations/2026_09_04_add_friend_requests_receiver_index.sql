-- Agrega índice en receiver_id para friend_requests.
-- getFriendRequests/acceptFriendRequest/rejectFriendRequest filtran por receiver_id
-- y el UNIQUE(sender_id, receiver_id) existente no cubre esa búsqueda como índice
-- independiente (solo sirve con sender_id como prefijo).

ALTER TABLE friend_requests
    ADD INDEX idx_receiver (receiver_id);
