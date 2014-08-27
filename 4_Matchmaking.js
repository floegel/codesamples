findTourneyOpponent: function(client){
	var that = this;
	this.findActiveTourney(function(err, tourneys){		
		var tourney = tourneys.shift(),
			userid = client.user._id;
		that.db.tourneyMemberCollection.findOne({ userid: userid, tourneyid: tourney._id }, function(err, member){
			if (err || tourneys.length == 0){
	    		this.log("TourneyManager: Failed to find tourney opponent. No active tourney found.", err);
	    		return;
	    	}
			var tp = 0;
			// its the users first tourney fight: add him as a tourney member
			if (!member){
				that.addTourneyMember(userid);
			}else{
				tp = member.tp;
			}
			// try to find players in current tourney that have more or equal tp, exclude the player himself
			var constraints = { tourneyid: tourney._id, userid: {$ne: userid}, tp: {$gte: tp} };
			that.db.tourneyMemberCollection.count(constraints, function(err, count){
				// nobody got more tp, find someone with less
				if (count == 0){
					constraints.tp = {$lt: tp};
				}
				// find at most 10 players and select a random one
				that.db.tourneyMemberCollection.find(constraints).limit(10).toArray(function(err, members){
					if (err || members.length == 0){
						return;
					}
					var member = _.sample(members);
					// got member, now find the user entry
					that.db.userCollection.findOne({ _id: member.userid }, function(err, user){
						if (err || !user){
							this.log("TourneyManager: Failed to find tourney opponent. No other players found.", err);
							return;	
						}
						var data = {
							fbuid: user.fbuid,
							name: user.name,
							avatarId: user.avatarId,
							upgrades: user.upgrades
						};
						client.emit("startTourneyFight", data);
					});
				});
			});
		});
	});
}