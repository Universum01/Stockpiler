import pymysql.cursors
import discord
from discord.ext.commands import Bot
from multiprocessing import Process
import re
import asyncio
from datetime import datetime, timedelta, timezone
import pytz
import urllib.request, json
from functools import cmp_to_key
import feedparser
import time

bot = Bot(command_prefix='!', case_insensitive=True)
active_stockpiles = "0"

connection = pymysql.connect(host='website',
     user='username',
     password='password',
     db='database',
     charset='utf8mb4',
     cursorclass=pymysql.cursors.DictCursor)

bot.remove_command('help')

monitoring_towns = []

server_time = 'Etc/GMT+8'  # DB servers time zone
mytimezone = 'US/Eastern'  # For a list:  https://gist.github.com/JellyWX/913dfc8b63d45192ad6cb54c829324ee

dynamic_map_data = []  # Hold all map data in memory, since we check it every 5 seconds its better to have in memory.

last_change = []

@bot.command(name='help', help='Provides information on the bot.')
async def helpc(ctx):
    await get_command_channel(ctx).send(f"The help command for Overwatch is !overhelp")
    try:
        await ctx.message.delete()
    except discord.NotFound as response:
        print(f"Unable to delete message, not found: {response}")


@bot.command(name='overhelp', help='Provides information on the bot.')
async def help_command(ctx):
    await get_command_channel(ctx).send(f"Command **!OverHelp** ran.\n"
                                        f"**!OP** - Displays current OP details\n"
                                        f"**!OPSET op details, up to 3000 characters** - Sets a !op message for 15 hours.\n"
                                        f"**!watch Town Name** - Will ping you if said Town Name changes status (Green/White/Blue)\n"
                                        f"**!watch** - Lists all towns you currently have pings set for. Also lists unwatch IDs\n"
                                        f"**!watch * ** - Lists all town EVERYONE currently has pings set for.\n"
                                        f"**!unwatch #** - Ends a !watch ping notification. Needs a delete ID #, you can get it with the !watch command.\n"
                                        f"**!unwatch * ** - Ends ALL !watch ping notifications for you, no ID needed.")
    await ctx.message.delete()
    return


def check_map_data():  # Checks that the region/map flat file exists, on war switchover it gets deleted.
    # Since this data only changes on a new war we save the data locally.
    global dynamic_map_data
    try:
        f = open("overwatch_regions/static_data/region_names.json")
        # Do something with the file
        f.close()
    except IOError:
        print("region_names.json not found, creating.")
        with urllib.request.urlopen(
                "https://war-service-live.foxholeservices.com/api/worldconquest/maps") as url:
            data = json.loads(url.read())
            data = json.dumps(data)
        f = open("overwatch_regions/static_data/region_names.json", "w+")
        f.write(str(data))
        f.close()



    #  Now for each region flat file
    #  Static data
    with open("overwatch_regions/static_data/region_names.json", "r") as f:
        region_data = json.loads(f.read())
    for map_names in region_data:
        try:
            f = open(f"overwatch_regions/static_data/{map_names}.json")
            # Do something with the file
            f.close()
        except IOError:
            print(f"Map static/{map_names}.json not found, creating.")
            with urllib.request.urlopen(
                    "https://war-service-live.foxholeservices.com/api/worldconquest/maps/" + map_names + "/static") as url:
                data = json.loads(url.read())
                data = json.dumps(data)
            f = open(f"overwatch_regions/static_data/{map_names}.json", "w+")
            f.write(str(data))
            f.close()
    #  Dynamic data

    try:
        f = open("overwatch_regions/dynamic_data/status.json")
        # Do something with the file
        f.close()
    except IOError:
        print("status.json not found, creating.")
        f = open("overwatch_regions/dynamic_data/status.json", "w+")
        f.write("")
        f.close()

    with open("overwatch_regions/static_data/region_names.json", "r") as f:
        region_data = json.loads(f.read())
    for map_names in region_data:
        try:
            f = open(f"overwatch_regions/dynamic_data/{map_names}.json")
            data = json.loads(f.read())
            dynamic_map_data.append(data)
            f.close()
        except IOError:
            print(f"Map dynamic/{map_names}.json not found, creating.")
            etag = ""
            with urllib.request.urlopen(
                    "https://war-service-live.foxholeservices.com/api/worldconquest/maps/" + map_names + "/dynamic/public") as url:
                data = json.loads(url.read())
                #data = json.dumps(data)
                etag = url.info()["ETag"].replace('"', '')
            temp = {"name": map_names, "etag": etag, "data": data}
            dynamic_map_data.append(temp)
            f = open(f"overwatch_regions/dynamic_data/{map_names}.json", "w+")
            f.write(str(json.dumps(temp)))
            f.close()





@bot.command(name='watch', help='')
async def watch_command(ctx, *args):
    async with ctx.channel.typing():
        canidates = []
        letter_cmp_key = cmp_to_key(letter_cmp)
        name = ""
        match = []
        counter = 0
        message = ""
        for x in args:
            name += x
            counter += 1
            if len(args) > counter:
                name += " "

        if len(name) == 0 or name == "*":
            if len(name) == 0:
                message = f"{ctx.message.author.mention} Currently Watching the following for YOU:\n"
                query = "SELECT * FROM overwatch_watch_list WHERE discord = '" + str(ctx.guild.name) + "' and user = '" \
                        + str(ctx.message.author) + "' ORDER BY id DESC"
            elif name == "*":
                message = f"{ctx.message.author.mention} Currently Watching the following for changes for {str(ctx.guild.name)}:\n"
                query = "SELECT * FROM overwatch_watch_list WHERE discord = '" + str(ctx.guild.name) + "' ORDER BY id DESC"
            else:
                message = f"{ctx.message.author.mention} Unknown error with your request, please try again. Error Code: 5UTtdTzH"
                print(f"Watch command error. Error with ARGS: '{name}' length: {len(name)}")
                await get_command_channel(ctx).send(message)
                await ctx.message.delete()
                return
            try:
                connection.ping()
                with connection.cursor() as cursor:
                    cursor.execute(query)
                    watchlist_details = cursor.fetchall()
            except pymysql.err.ProgrammingError as except_detail:
                print(except_detail)
                message = f"{ctx.message.author.mention} I don't have anything in the list for you or " \
                          f"your discord, you can use " \
                          f"**!watch Town Name** and i will alert you of any changes in ownership for that town.\n" \
                          f"You can also use the command **!watch * **to see what everyone in this discord is watching."
            

            if watchlist_details is not None and len(watchlist_details) > 0:
                for watch in watchlist_details:
                    if name == "*":
                        message += f"{watch['user_display']}: Watching **{watch['name']}** in region {watch['region'].replace('Hex', '')}\n"
                    else:
                        message += f"Watching **{watch['name']}** in region {watch['region'].replace('Hex', '')} Command **!unwatch {watch['id']}** to end this watch request.\n"

            message += "\n If you wish to be notified for changes to a town yourself, just use the command " \
                       "**!watch Town Name** and I will let you know if that town changes hands!"

        else:
            name_array = name.replace("'", "").replace('"', '').split(" ")
            with open("overwatch_regions/static_data/region_names.json", "r") as f:
                data = json.loads(f.read())
                for map in data:
                    if len(match) == 0:
                        with open("overwatch_regions/static_data/" + map + ".json", "r") as f:
                            static_data = json.loads(f.read())
                            for x in static_data["mapTextItems"]:
                                if len(match) == 0:
                                    score = 0
                                    map_name = x["text"].replace("'", "").replace('"', '').split(" ")
                                    for z in map_name:
                                        if len(match) == 0:
                                            for y in name_array:
                                                if str(y).upper() in str(z).upper() and len(match) == 0:
                                                    score += len(str(y).upper())
                                    if score > 0:
                                        if score == len(name):
                                            match = [x["text"], map]
                                        else:
                                            temp = {"name": x["text"], "weight": score, "region": map}
                                            canidates.append(temp)
            if len(match) == 0 and len(canidates) > 0: # no exact match but some possibles.
                canidates.sort(key=letter_cmp_key)
                match = [canidates[0]["name"], canidates[0]["region"]]  # Lets assume they meant the one with the highest weight

            if len(match) > 0:  # we picked a winner
                query = "SELECT * FROM overwatch_watch_list WHERE discord = '" + str(ctx.guild.name) + "' and user = '"\
                        + str(ctx.message.author) + "' and name = '" + match[0].replace("'", "") + "' ORDER BY id DESC"
                print(query)
                try:
                    connection.ping()
                    with connection.cursor() as cursor:
                        cursor.execute(query)
                        watchlist_details = cursor.fetchone()
                except pymysql.err.ProgrammingError as except_detail:
                    print(except_detail)
                    message = f"{ctx.message.author.mention} Unknown error when setting your request. Please try again. Error Code: DYDfv479"
                

                if watchlist_details is not None and len(watchlist_details) > 0:
                    message = f"{ctx.message.author.mention} You already have a watch set for {match[0]}, not to worry, iv got my eye on it."
                    id = watchlist_details["id"]
                    message += f"\n-To cancel this watch request use command !unwatch {id}"
                else:
                    query = f"INSERT INTO overwatch_watch_list (name, user, region, discord, user_display, user_id) VALUES (%s, %s, %s, %s, %s, %s)"
                    data = (match[0], str(ctx.message.author), match[1].replace("'", ""), str(ctx.guild.name), str(ctx.message.author.display_name), str(ctx.message.author.id))
                    passed = False
                    try:
                        connection.ping()
                        with connection.cursor() as cursor:
                            cursor.execute(query, data)
                        connection.commit()
                        passed = True
                    except pymysql.err.ProgrammingError as except_detail:
                        print(except_detail)
                        message = f"{ctx.message.author.mention} Unknown error when setting your request. Please try again. Error Code: 5uGJiDnJ"
                    

                    if passed:
                        message = f"{ctx.message.author.mention} Now watching **{match[0]}**  in {match[1].replace('Hex', '')} region for any changes, I will ping you if its status changes."

                        query = "SELECT * FROM overwatch_watch_list WHERE discord = '" + str(ctx.guild.name) + "' and user = '" + str(ctx.message.author) + "' and name = '" + match[0].replace("'", "") + "' ORDER BY id DESC"

                        try:
                            connection.ping()
                            with connection.cursor() as cursor:
                                cursor.execute(query)
                                watchlist_details = cursor.fetchone()
                        except pymysql.err.ProgrammingError as except_detail:
                            print(except_detail)
                        
                        if watchlist_details is not None:
                            id = watchlist_details["id"]
                            message += f"\n-To cancel this watch request use command !unwatch {id}"
            else:
                message = f"Unable to find any area on the map with **{name}** in it. Perhaps its not on the map this war? Error Code: w9CcIY0C"

        await get_command_channel(ctx).send(message)
        await ctx.message.delete()
        return




@bot.command(name='unwatch', help='')
async def unwatch_command(ctx, arg1):
    async with ctx.channel.typing():
        query = "SELECT * FROM overwatch_watch_list WHERE id = " + str(arg1)

        try:
            connection.ping()
            with connection.cursor() as cursor:
                cursor.execute(query)
                watchlist_details = cursor.fetchall()
        except pymysql.err.ProgrammingError as except_detail:
            print(except_detail)
        

        if len(watchlist_details) == 1:
            if watchlist_details[0]['user'] == str(ctx.message.author) and watchlist_details[0]['discord'] == str(ctx.guild.name):
                query = "DELETE FROM overwatch_watch_list WHERE id = %s"
                data = (str(arg1))
                try:
                    connection.ping()
                    with connection.cursor() as cursor:
                        cursor.execute(query, data)
                        message = f"{ctx.message.author.mention} Watch of {watchlist_details[0]['name']} in " \
                                  f"{watchlist_details[0]['region'].replace('Hex', '')} has been deleted. " \
                                  f"You will no longer be pinged when changes occur.\n" \
                                  f"You can delete ALL of YOUR watch requests with !unwatch *"
                    connection.commit()
                except pymysql.err.ProgrammingError as except_detail:
                    print(except_detail)
                    message = f"{ctx.message.author.mention} Unknown error when deleting your Watch request, " \
                              f"please try again. Error Code: kLA4Kk2Z"
                
        elif len(watchlist_details) > 0:
            fail = False
            for x in watchlist_details:
                if x['user'] == str(ctx.message.author) and x['discord'] == str(ctx.guild.name):
                    query = "DELETE FROM overwatch_watch_list WHERE id = %s"
                    data = (str(arg1))
                    try:
                        connection.ping()
                        with connection.cursor() as cursor:
                            cursor.execute(query, data)
                            message = f"{ctx.message.author.mention} All watch's have been canceled for you, " \
                                      f"you will no longer be notified of any of them."
                        connection.commit()
                    except pymysql.err.ProgrammingError as except_detail:
                        print(except_detail)
                        fail = True
                    
            if fail:
                message = f"{ctx.message.author.mention} Unknown error when deleting some of your Watch requests, " \
                          f"please try again. Error Code: E6r0begd"
        else:
            message = f"{ctx.message.author.mention} Unable to locate that Watch ID, did you mistype a number? " \
                      f"Error Code: R54AYmNy\n Type " \
                      f"**!watch** to see a list of all areas your tracking and their delete codes or type " \
                      f"**!watch * **to see what everyone in this discord is tracking."


        await get_command_channel(ctx).send(message)
        await ctx.message.delete()
        return


def letter_cmp(a, b):
    if a["weight"] > b["weight"]:
        return -1
    elif a["weight"] == b["weight"]:
        return 0
    else:
        return 1


def clean_old_ops():
    EST = pytz.timezone(mytimezone)
    now = datetime.now(EST)
    future_plus_15_hours = now - timedelta(hours=15)
    query = "DELETE FROM overw_op WHERE added < %s"
    data = (future_plus_15_hours)
    try:
        connection.ping()
        with connection.cursor() as cursor:
            cursor.execute(query, data)
        connection.commit()
    except pymysql.err.ProgrammingError as except_detail:
        print(except_detail)
        return
    
    return


@bot.command(name='op', help='Provides information on current stock levels for the given stockpile. use !stock or !stock Stockpile Name')
async def op_command(ctx, *args):
    async with ctx.channel.typing():
        global server_time
        clean_old_ops()

        message = "!OP - Command Ran, shows the status of Operations for the last 15 hours.\n"

        print(f"OP command ran for guild '{ctx.guild.name}'")
        EST = pytz.timezone('US/Eastern')
        now = datetime.now(EST)
        if check_discord_status(ctx) != 1:
            await get_command_channel(ctx).send("ERROR: Command unavailible. Error Code: GxuiSzi4")
            print(f"!OP Command ran for BANNED guild: {ctx.guild.name}")
            return

        query = "SELECT * FROM overw_op WHERE discord = '" + ctx.guild.name + "' ORDER BY id DESC"

        try:
            connection.ping()
            with connection.cursor() as cursor:
                cursor.execute(query)
                op_details = cursor.fetchall()
        except pymysql.err.ProgrammingError as except_detail:
            print(except_detail)
            return
        
        string = ""
        server_timez = pytz.timezone(server_time)

        if len(op_details) > 0:
            for x in op_details:
                server_time = server_timez.localize(x['added'])
                time_diffrence = now - server_time
                time_diffrence = round(time_diffrence.total_seconds())
                time_frame = ""
                print(time_diffrence)
                if time_diffrence < 60:
                    time_diffrence = round(time_diffrence)
                    time_frame = ''
                    if time_diffrence > 1:
                        time_frame = 's'
                    time_frame = f" - Posted **{time_diffrence}** Second{time_frame} Ago."
                elif time_diffrence > 59 and time_diffrence < 3600:
                    time_diffrence = round(time_diffrence /60)
                    time_frame = ''
                    if time_diffrence > 1:
                        time_frame = 's'
                    time_frame = f" - Posted **{time_diffrence}** Minute{time_frame} Ago."
                else:
                    time_diffrence = round(time_diffrence / 3600, 1)
                    time_frame = ''
                    if time_diffrence > 1:
                        time_frame = 's'
                    time_frame = f" - Posted **{time_diffrence}** Hour{time_frame} Ago."
                string += f"Current OPs:\n{x['id']}.) **{x['user_name']}:** {x['text']} {time_frame}\n"
        else:
            string += f"No OPs posted. Maybe find out whats going on and use !opset to fill other people in?\n"
        message += f"{ctx.message.author.mention}\n{string}"
        await get_command_channel(ctx).send(message)
        await ctx.message.delete()
        return


def check_discord_status(ctx):
    query = "SELECT * FROM discords_list WHERE discord_name = '" + ctx.guild.name + "'"

    try:
        connection.ping()
        with connection.cursor() as cursor:
            cursor.execute(query)
            discord_details = cursor.fetchone()
    except pymysql.err.ProgrammingError as except_detail:
        print(except_detail)
        return 0
    

    if len(discord_details) > 0:
        status = discord_details['status']
        return status

    else:
        pass

        # Add insert here.
    return 0

def get_command_channel(ctx):
    query = "SELECT commands_channel FROM discords_list WHERE discord_name = '" + str(ctx.guild.name) + "'"
    try:
        connection.ping()
        with connection.cursor() as cursor:
            cursor.execute(query)
            channel_name = cursor.fetchone()
    except pymysql.err.ProgrammingError as except_detail:
        print(except_detail)
        return ctx
    
    if channel_name['commands_channel'] is not None and len(channel_name['commands_channel']) > 0:
        channel = discord.utils.get(bot.get_all_channels(), guild__name=ctx.guild.name,
                                name=channel_name['commands_channel'])
        return channel
    else:
        return ctx

@bot.event
async def on_ready():
    global dynamic_map_data, last_change


    with open("overwatch_regions/static_data/region_names.json", "r") as f:
        region_data = json.loads(f.read())
    for map_names in region_data:
        try:
            f = open(f"overwatch_regions/dynamic_data/{map_names}.json")
            data = json.loads(f.read())
            dynamic_map_data.append(data)
            f.close()
        except IOError:
            check_map_data()
    last_status = ""
    with open("overwatch_regions/dynamic_data/status.json", "r") as f:
        file = f.read()
        if len(str(file)) > 0:
            data = json.loads(file)
            if len(data) == 2:
                last_change = data

    overwatch_checkchanges = time.time()
    run_overwatch_every_seconds = 5

    overwatch_5min_running_average = 0
    overwatch_5min_running_average_timer = time.time()

    print("Overwatch Online")
    while True:
        if (time.time() - overwatch_checkchanges) >= run_overwatch_every_seconds:
            overwatch_checkchanges = time.time()
            startTime = time.time()


            status = ""
            if len(last_change) > 0:
                status += " " + last_change[0] + " - "
                time_string = ""
                diff = round(time.time() - last_change[1])
                if diff < 3600:
                    diff = round((diff / 60))
                    time_string += "minute"
                    if diff < 120:
                        time_string += "s"
                    time_string += " ago."
                elif diff >= 3600 and diff < 86400:
                    diff = round((diff / 3600))
                    time_string = "hour"
                    if diff < 7200:
                        time_string += "s"
                    time_string += " ago."
                else:
                    diff = round((diff / 86400))
                    time_string = "day ago."

                status += f"{diff} {time_string}"
            if last_status != status:
                last_status = status
                activity = discord.Game(name=f"!OverHelp {status}")
                await bot.change_presence(status=None, activity=activity)
            await check_alerts()
            executionTime = (time.time() - startTime)
            if (time.time() - overwatch_5min_running_average_timer) >= 300:
                avg_time = round(overwatch_5min_running_average / (300 / run_overwatch_every_seconds), 4)
                if executionTime > (run_overwatch_every_seconds - 1):
                    print(f"WARNING!! Overwatch took {executionTime} seconds to run! Thats approching the rerun "
                          f"function time of {run_overwatch_every_seconds} seconds!")
                else:
                    print(f"Overwatch 5 minute running average: {avg_time} seconds.")

                overwatch_5min_running_average = 0
                overwatch_5min_running_average_timer = time.time()
            overwatch_5min_running_average += executionTime
        else:
            await asyncio.sleep(0.5)

async def check_alerts():
    global dynamic_map_data, monitoring_towns, last_change
    dynamic_map_data_temp = []
    for dy_data in dynamic_map_data:
        d = feedparser.parse("https://war-service-live.foxholeservices.com/api/worldconquest/maps/" + dy_data[
            'name'] + "/dynamic/public")
        if "etag" not in d:
            print("No ETag")
            print(d)
            return
        etag = d.etag.replace('"', '')
        static_data = ""
        if etag != dy_data["etag"]:
            change = []
            print(
                f"Change detected in {dy_data['name'].replace('Hex', '')}. Past ETag: {dy_data['etag']} Current ETag: {etag}")
            try:
                f = open(f"overwatch_regions/static_data/{dy_data['name']}.json")
                static_data = json.loads(f.read())
                f.close()
            except IOError:
                pass

            with urllib.request.urlopen(
                    "https://war-service-live.foxholeservices.com/api/worldconquest/maps/" + dy_data[
                        'name'] + "/dynamic/public") as url:
                data = json.loads(url.read())
            for map in data["mapItems"]:
                for x in dy_data["data"]["mapItems"]:
                    if x["x"] == map["x"] and x["y"] == map["y"]:
                        if x["teamId"] != map["teamId"]:
                            if (map["iconType"] >= 5 and map["iconType"] <= 7) or (
                                    map["iconType"] >= 45 and map["iconType"] <= 47):
                                print(f'Change found in : {map["x"]}, {map["y"]}')
                                change.append(
                                    {"nowTeam": map["teamId"], "thenTeam": x["teamId"], "icon": map["iconType"],
                                     "flags": map["flags"], "x": map["x"], "y": map["y"]})
                            else:
                                print(f"teamID changed but icon type is only: {map['iconType']}")

                        print(f"Map: {map}")
                        print(f"x: {x}")

            #  This whole slice of crazy is because the Dynamic data goes not list location names,
            #  only x,y so i have to match the xy in static data to dynamic just to figure out what dam town it is.
            updated = []
            varience = 0.038  # If x and Y are within X, the name and the marker should be ontop of one another,
            # in my math the names and icons were only about 0.0015 off.
            print(change)
            if len(change) > 0:
                for find_name in change:
                    winner = {"data": {}, "total": 66}
                    for x in static_data["mapTextItems"]:
                        if x["mapMarkerType"] == "Major":
                            checkx = 0
                            checky = 0
                            total = 0
                            if find_name["x"] > x["x"]:
                                checkx = find_name["x"] - x["x"]
                            else:
                                checkx = x["x"] - find_name["x"]
                            if find_name["y"] > x["y"]:
                                checky = find_name["y"] - x["y"]
                            else:
                                checky = x["y"] - find_name["y"]
                            checky = round(checky, 3)
                            checkx = round(checkx, 3)
                            total = checkx + checky
                            print(f'{x["text"].replace("Hex", "")} checkx: {checkx} checky {checky} TOTAL: {total}')
                            if total < varience:
                                print("PASS")
                                if total < winner["total"]:
                                    print("WINNER!")
                                    winner = {"data": {"name": x["text"], "nowTeam": find_name["nowTeam"],
                                                       "thenTeam": find_name["thenTeam"], "icon": find_name["icon"],
                                                       "flags": find_name["flags"]}, "total": total}

                    if winner["total"] > 0:
                        updated.append(winner["data"])
                print("")
                print(f"UPDATED: {updated}")
                if len(updated) > 0 and len(updated[0]) > 0:
                    for update in updated:
                        query = "SELECT * FROM overwatch_watch_list WHERE name = '" + update['name'].replace("'",
                                                                                                             "") + "'"
                        last_change = [f"{update['name']} from {update['thenTeam']} -> {update['nowTeam']}", time.time()]
                        f = open("overwatch_regions/dynamic_data/status.json", "w+")
                        f.write(json.dumps(last_change))
                        f.close()
                        try:
                            connection.ping()
                            with connection.cursor() as cursor:
                                cursor.execute(query)
                                notice_list = cursor.fetchall()
                        except pymysql.err.ProgrammingError as except_detail:
                            print(except_detail)
                            return ctx
                        
                        print(f"Select: {notice_list}")
                        if notice_list is not None:
                            for g in notice_list:
                                #  Get the channel the bot should post in
                                query = "SELECT commands_channel FROM discords_list WHERE discord_name = '" \
                                        + str(g["discord"]) + "'"  # TODO change this to its discord ID
                                try:
                                    connection.ping()
                                    with connection.cursor() as cursor:
                                        cursor.execute(query)
                                        channel_name = cursor.fetchone()
                                except pymysql.err.ProgrammingError as except_detail:
                                    print(except_detail)
                                

                                if channel_name['commands_channel'] is not None and len(
                                        channel_name['commands_channel']) > 0:
                                    channel = discord.utils.get(bot.get_all_channels(), guild__name=str(g["discord"]),
                                                                name=channel_name['commands_channel'])
                                    message = f"<@!{g['user_id']}> **{update['name']}** has changed hands, going from {update['thenTeam']} -> {update['nowTeam']}"
                                    try:
                                        await channel.send(message)
                                    except discord.Forbidden as response:
                                        if "error code: 50001" in response:
                                            print(
                                                f"No access to channel {channel_name['commands_channel']} for discord {g['discord']}")
                                else:
                                    print("Discord not listed")
                                    pass
                                    #  TODO Handle when the discord is not listed.

            # update the dynamic_map_data_temp and flat files
            temp = {"name": dy_data['name'], "etag": etag, "data": data}
            dynamic_map_data_temp.append(temp)
            f = open(f"overwatch_regions/dynamic_data/{dy_data['name']}.json", "w+")
            f.write(str(json.dumps(temp)))
            f.close()
        else:
            dynamic_map_data_temp.append(dy_data)
    dynamic_map_data = dynamic_map_data_temp

def cleanhtml(raw_html):
  cleanr = re.compile('<.*?>')
  cleantext = re.sub(cleanr, '', raw_html)
  return cleantext


@bot.command(name='test', help='')
async def opset_command(ctx, *args):
    print(ctx.message.author.display_name)


@bot.command(name='opset', help='')
async def opset_command(ctx, *args):
    clean_old_ops()

    message = "!OPSET - Command Ran, Sets the OP message for people that use the !OP command.\n"

    status = check_discord_status(ctx)
    if status != 1:
        print(f"Status: {status}")
        await get_command_channel(ctx).send("ERROR: Command unavailible. Error Code: A9eMV6ql")
        return
    string = ""
    for x in args:
        string += x + " "
    string = cleanhtml(string)

    if "select * from" in string.lower():
        pass  # add ban code

    query = f"INSERT INTO overw_op (user, user_name, text, discord) VALUES (%s, %s, %s, %s)"
    data = (str(ctx.message.author), str(ctx.message.author.display_name), string, str(ctx.guild.name))
    try:
        connection.ping()
        with connection.cursor() as cursor:
            cursor.execute(query, data)
        connection.commit()
    except pymysql.err.ProgrammingError as except_detail:
        print(except_detail)
        return
    

    message += f"{ctx.message.author.mention} OP Updated."

    await get_command_channel(ctx).send(message)

    await ctx.message.delete()
    return


def startdiscord(token):
    bot.run(token)


if __name__ == "__main__":
    print("Starting Overwatch...")
    check_map_data()
    p = Process(target=startdiscord, args=('discord_bot_token',))
    p.start()
