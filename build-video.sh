#!/usr/bin/env bash
# Builds the homepage hero background montage from real Sky Link footage.
#
#   ./build-video.sh inspect [extra files...]
#       ffprobe every clip in footage/ (plus any extra files) and write a
#       contact sheet per clip (1 frame per second, 6 per row) to
#       footage/_review/. Tile N (counting from 0, left to right, top to
#       bottom) is second N of the clip.
#
#   ./build-video.sh build
#       Read footage/shotlist.txt and write, into public/assets/videos/hero/:
#       hero-desktop.mp4, hero-desktop.webm, hero-mobile.mp4, poster.jpg.
#       Fails (and leaves the live files untouched) if a size limit is exceeded.
#
# Shot list format (footage/shotlist.txt), one shot per line, in story order:
#   path | start_seconds | seconds_in_final_cut | label | options
# options (optional, ';' separated):
#   stab                      stabilise the shot (needs ffmpeg built with vidstab)
#   blur=X:Y:W:H[,X:Y:W:H]    blur boxes in final 1920x1080 coordinates, whole shot
#   crop=W:H:X:Y              crop the source first (default: letterbox bars are
#                             detected and removed automatically)
#   nocrop                    keep the full source frame
# Example:
#   footage/planning-wall.mp4 | 3.0 | 4 | planning |
#   footage/cctv-install.mp4  | 12.5 | 4 | cctv | stab; blur=1210:340:380:220
#
# Requirements: ffmpeg + ffprobe on PATH (libx264, libvpx-vp9; vidstab for "stab").
set -euo pipefail
export MSYS_NO_PATHCONV=1 # Git Bash: never rewrite ffmpeg filter arguments as paths

cd "$(dirname "$0")"

# ---- Settings -------------------------------------------------------------
FOOTAGE_DIR="footage"
SHOTLIST="$FOOTAGE_DIR/shotlist.txt"
REVIEW_DIR="$FOOTAGE_DIR/_review"
WORK_DIR="$FOOTAGE_DIR/.build"
OUT_DIR="public/assets/videos/hero"

SPEED=0.85          # playback speed of the footage (slow motion)
XFADE=0.4           # crossfade length in seconds
FPS=30
MIN_TOTAL=20        # seconds
MAX_TOTAL=30

DESKTOP_MAX_BYTES=$((8 * 1024 * 1024))
MOBILE_MAX_BYTES=$((3 * 1024 * 1024))
POSTER_MAX_BYTES=$((150 * 1024))
MOBILE_MIN_SSIM=0.95 # below this the mobile encode counts as blocky

# Brand grade: cool shadows toward deep navy, highlights toward #4da3ff, a bit darker.
GRADE="eq=contrast=1.06:brightness=-0.045:saturation=0.82:gamma=0.96,colorbalance=rs=-0.07:gs=-0.02:bs=0.09:rm=-0.04:gm=0.0:bm=0.06:rh=-0.03:gh=0.01:bh=0.05"

VIDEO_EXT_RE='\.(mp4|mov|m4v|mkv|avi|webm|mts)$'

# ---- Helpers ----------------------------------------------------------------
die() { echo "ERROR: $*" >&2; exit 1; }
calc() { awk "BEGIN { printf \"%.4f\", $* }"; }
size_of() { wc -c < "$1" | tr -d ' '; }
human() { awk -v b="$1" 'BEGIN { printf (b >= 1048576 ? "%.2f MB" : "%.0f KB"), (b >= 1048576 ? b / 1048576 : b / 1024) }'; }
trim() { local s="$1"; s="${s#"${s%%[![:space:]]*}"}"; printf '%s' "${s%"${s##*[![:space:]]}"}"; }

require_tools() {
    command -v ffmpeg >/dev/null || die "ffmpeg not found on PATH (winget install --id Gyan.FFmpeg -e)."
    command -v ffprobe >/dev/null || die "ffprobe not found on PATH."
    local encoders
    encoders="$(ffmpeg -hide_banner -encoders 2>/dev/null)"
    grep -q libx264 <<<"$encoders" || die "ffmpeg lacks libx264."
    grep -q libvpx-vp9 <<<"$encoders" || die "ffmpeg lacks libvpx-vp9."
}

has_vidstab() { ffmpeg -hide_banner -filters 2>/dev/null | grep -q vidstabdetect; }

list_clips() {
    [ -d "$FOOTAGE_DIR" ] || return 0
    find "$FOOTAGE_DIR" -maxdepth 1 -type f | grep -iE "$VIDEO_EXT_RE" | sort || true
}

probe_row() {
    local f="$1" info rot
    info="$(ffprobe -v error -select_streams v:0 \
        -show_entries stream=width,height,r_frame_rate:format=duration \
        -of default=noprint_wrappers=1 "$f")"
    rot="$(ffprobe -v error -select_streams v:0 \
        -show_entries stream_side_data=rotation:stream_tags=rotate \
        -of default=noprint_wrappers=1:nokey=1 "$f" | head -n1)"
    awk -v f="$f" -v rot="${rot:-0}" -F= '
        $1 == "width" { w = $2 } $1 == "height" { h = $2 }
        $1 == "r_frame_rate" { split($2, r, "/"); fps = (r[2] ? r[1] / r[2] : r[1]) }
        $1 == "duration" { d = $2 }
        END { printf "%-48s %5dx%-5d %6.2f fps %8.2f s  rotation %s\n", f, w, h, fps, d, rot }' <<<"$info"
}

# ---- inspect ---------------------------------------------------------------
cmd_inspect() {
    require_tools
    mkdir -p "$REVIEW_DIR"
    local clips=()
    mapfile -t clips < <(list_clips)
    clips+=("$@")
    [ "${#clips[@]}" -gt 0 ] || die "No clips in $FOOTAGE_DIR/. Add footage, then re-run."

    echo "Clip                                              Size        FPS      Duration  Rotation"
    local f name dur rows
    for f in "${clips[@]}"; do
        probe_row "$f"
    done

    for f in "${clips[@]}"; do
        name="$(basename "${f%.*}")"
        dur="$(ffprobe -v error -show_entries format=duration -of csv=p=0 "$f")"
        rows="$(awk -v d="$dur" 'BEGIN { r = int((d + 5) / 6); print (r < 1 ? 1 : r) }')"
        ffmpeg -v error -y -i "$f" \
            -vf "fps=1,scale=320:-2,tile=6x${rows}:padding=4:color=black" \
            -frames:v 1 -update 1 -q:v 4 "$REVIEW_DIR/${name}-sheet.jpg"
        echo "contact sheet: $REVIEW_DIR/${name}-sheet.jpg"
    done
}

# ---- build -----------------------------------------------------------------
blur_chain() {
    # $1 = "X:Y:W:H[,X:Y:W:H]" -> filter fragment applied to [v] producing [v]
    local boxes="$1" out="" i=0 b x y w h
    IFS=',' read -r -a list <<<"$boxes"
    for b in "${list[@]}"; do
        IFS=':' read -r x y w h <<<"$b"
        out+=",split[base$i][cut$i];[cut$i]crop=$w:$h:$x:$y,boxblur=24:3[blur$i];[base$i][blur$i]overlay=$x:$y"
        i=$((i + 1))
    done
    printf '%s' "$out"
}

detect_crop() {
    # Most common cropdetect result over the window; empty if there are no bars.
    local src="$1" start="$2" len="$3" crop size
    crop="$(ffmpeg -hide_banner -ss "$start" -t "$len" -i "$src" \
        -vf "cropdetect=limit=24:round=2:reset=1" -f null - 2>&1 \
        | grep -oE 'crop=[0-9:]+' | sort | uniq -c | sort -nr | awk 'NR == 1 { sub("crop=", "", $2); print $2 }')"
    size="$(ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=s=x:p=0 "$src")"
    case "$crop" in
        "" | "${size%x*}:${size#*x}:0:0") ;;
        *) printf '%s' "$crop" ;;
    esac
}

prepare_shot() {
    # Cut, slow down, stabilise, scale/crop, grade and blur one shot into a
    # high-quality intermediate.
    local idx="$1" src="$2" start="$3" length="$4" opts="$5"
    local out="$WORK_DIR/shot-$idx.mp4"
    local src_len filters="" stab=0 blur=""
    src_len="$(calc "$length * $SPEED + 0.1")"

    local opt crop="auto"
    IFS=';' read -r -a parts <<<"$opts"
    for opt in "${parts[@]}"; do
        opt="$(trim "$opt")"
        case "$opt" in
            stab) stab=1 ;;
            blur=*) blur="${opt#blur=}" ;;
            crop=*) crop="${opt#crop=}" ;;
            nocrop) crop="" ;;
            "") ;;
            *) die "Unknown option '$opt' for $src" ;;
        esac
    done

    # Remove baked-in letterbox / pillarbox bars before scaling.
    if [ "$crop" = "auto" ]; then
        crop="$(detect_crop "$src" "$start" "$src_len")"
    fi
    if [ -n "$crop" ]; then
        echo "  crop $crop" >&2
        filters+="crop=$crop,"
    fi

    if [ "$stab" = 1 ]; then
        has_vidstab || die "Shot $src asks for 'stab' but this ffmpeg has no vidstab."
        ffmpeg -v error -y -ss "$start" -t "$src_len" -i "$src" \
            -vf "${crop:+crop=$crop,}vidstabdetect=shakiness=6:accuracy=15:result=$WORK_DIR/shot-$idx.trf" -f null -
        filters+="vidstabtransform=input=$WORK_DIR/shot-$idx.trf:smoothing=20:zoom=4,unsharp=5:5:0.6:3:3:0.3,"
    fi

    filters+="setpts=PTS/$SPEED,framerate=fps=$FPS,scale=1920:1080:force_original_aspect_ratio=increase:flags=lanczos,crop=1920:1080,setsar=1,$GRADE"
    [ -n "$blur" ] && filters+="$(blur_chain "$blur")"
    filters+=",trim=duration=$length,setpts=PTS-STARTPTS,format=yuv420p"

    ffmpeg -v error -y -ss "$start" -t "$src_len" -i "$src" -an \
        -filter_complex "[0:v]${filters}[v]" -map "[v]" \
        -c:v libx264 -preset veryfast -crf 12 -r "$FPS" "$out"
    echo "$out"
}

encode_h264() {
    # $1 in, $2 out, $3 scale (WxH), $4 crf, $5 maxrate, $6 bufsize, $7 profile
    ffmpeg -v error -y -i "$1" -an \
        -vf "scale=$3:flags=lanczos,format=yuv420p" \
        -c:v libx264 -preset slow -profile:v "$7" -crf "$4" -maxrate "$5" -bufsize "$6" \
        -r "$FPS" -g $((FPS * 2)) -video_track_timescale "$FPS" -movflags +faststart "$2"
    # Timescale = fps so every frame lasts exactly 1/fps and the file's duration
    # is exactly frames/fps (no short last frame at the loop point).
}

cmd_build() {
    require_tools
    [ -f "$SHOTLIST" ] || die "Missing $SHOTLIST. Run './build-video.sh inspect', pick shots, then write it (format at the top of this script)."

    rm -rf "$WORK_DIR"
    mkdir -p "$WORK_DIR" "$OUT_DIR" "$REVIEW_DIR"

    # 1. Prepare each shot.
    local shots=() lengths=() line src start length label opts n=0
    while IFS= read -r line || [ -n "$line" ]; do
        line="$(trim "${line%%#*}")"
        [ -z "$line" ] && continue
        IFS='|' read -r src start length label opts <<<"$line"
        src="$(trim "$src")"; start="$(trim "$start")"; length="$(trim "$length")"
        label="$(trim "${label:-}")"; opts="$(trim "${opts:-}")"
        [ -f "$src" ] || die "Shot file not found: $src"
        awk -v l="$length" 'BEGIN { exit !(l >= 3 && l <= 5) }' \
            || echo "WARNING: $src is ${length}s; the brief asks for 3-5 s per shot." >&2
        echo "shot $n: $label  ($src from ${start}s, ${length}s)"
        shots+=("$(prepare_shot "$n" "$src" "$start" "$length" "$opts")")
        lengths+=("$length")
        n=$((n + 1))
    done <"$SHOTLIST"
    [ "$n" -ge 2 ] || die "Need at least 2 shots in $SHOTLIST (brief: 6-8)."
    [ "$n" -ge 6 ] && [ "$n" -le 8 ] || echo "WARNING: $n shots; the brief asks for 6-8." >&2

    # 2. Montage with crossfades. The first XFADE seconds of shot 0 are appended
    #    and the montage's first XFADE seconds are trimmed, so the last frame
    #    dissolves into the first and the loop has no jump.
    ffmpeg -v error -y -i "${shots[0]}" -t "$XFADE" -c:v libx264 -preset veryfast -crf 12 "$WORK_DIR/loop-head.mp4"
    local inputs=() graph="" prev="[0:v]" offset=0 i
    for i in "${!shots[@]}"; do inputs+=(-i "${shots[$i]}"); done
    inputs+=(-i "$WORK_DIR/loop-head.mp4")
    for i in $(seq 1 "$n"); do
        offset="$(calc "$offset + ${lengths[$((i - 1))]} - $XFADE")"
        graph+="${prev}[$i:v]xfade=transition=fade:duration=$XFADE:offset=$offset[x$i];"
        prev="[x$i]"
    done
    graph+="${prev}trim=start=$XFADE,setpts=PTS-STARTPTS,format=yuv420p[out]"

    local master="$WORK_DIR/master.mp4"
    ffmpeg -v error -y "${inputs[@]}" -filter_complex "$graph" -map "[out]" -an \
        -c:v libx264 -preset medium -crf 14 -r "$FPS" "$master"

    local total
    total="$(ffprobe -v error -show_entries format=duration -of csv=p=0 "$master")"
    echo "montage length: ${total}s"
    awk -v t="$total" -v a="$MIN_TOTAL" -v b="$MAX_TOTAL" 'BEGIN { exit !(t >= a - 0.1 && t <= b + 0.1) }' \
        || die "Montage is ${total}s; it must be ${MIN_TOTAL}-${MAX_TOTAL}s. Adjust shot lengths in $SHOTLIST."

    # 3. Desktop H.264: best quality (lowest CRF in 24-26) that fits under the limit.
    local crf size ok=0
    for crf in 24 25 26; do
        encode_h264 "$master" "$WORK_DIR/hero-desktop.mp4" 1920:1080 "$crf" 3M 6M high
        size="$(size_of "$WORK_DIR/hero-desktop.mp4")"
        echo "hero-desktop.mp4  crf $crf  $(human "$size")"
        if [ "$size" -le "$DESKTOP_MAX_BYTES" ]; then ok=1; break; fi
    done
    [ "$ok" = 1 ] || die "hero-desktop.mp4 is over 8 MB even at CRF 26. Shorten the montage or use steadier shots."
    local desktop_size="$size"

    # 4. Desktop VP9: must not be larger than the MP4.
    ok=0
    for crf in 34 37 40; do
        ffmpeg -v error -y -i "$master" -an -c:v libvpx-vp9 -crf "$crf" -b:v 2500k -maxrate 3M -bufsize 6M \
            -deadline good -cpu-used 2 -row-mt 1 -tile-columns 2 -g $((FPS * 2)) -pix_fmt yuv420p \
            -r "$FPS" "$WORK_DIR/hero-desktop.webm"
        size="$(size_of "$WORK_DIR/hero-desktop.webm")"
        echo "hero-desktop.webm crf $crf  $(human "$size")"
        if [ "$size" -le "$desktop_size" ]; then ok=1; break; fi
    done
    [ "$ok" = 1 ] || die "hero-desktop.webm stays larger than hero-desktop.mp4."

    # 5. Mobile H.264 960x540: best quality that fits, then a blockiness check.
    ok=0
    for crf in 23 24 25 26 27; do
        encode_h264 "$master" "$WORK_DIR/hero-mobile.mp4" 960:540 "$crf" 1200k 2400k main
        size="$(size_of "$WORK_DIR/hero-mobile.mp4")"
        echo "hero-mobile.mp4   crf $crf  $(human "$size")"
        if [ "$size" -le "$MOBILE_MAX_BYTES" ]; then ok=1; break; fi
    done
    [ "$ok" = 1 ] || die "hero-mobile.mp4 is over 3 MB even at CRF 27."

    local ssim
    ssim="$(ffmpeg -hide_banner -i "$WORK_DIR/hero-mobile.mp4" -i "$master" \
        -lavfi "[1:v]scale=960:540:flags=lanczos[ref];[0:v][ref]ssim" -f null - 2>&1 \
        | sed -n 's/.*All:\([0-9.]*\).*/\1/p' | tail -n1)"
    echo "hero-mobile.mp4   SSIM vs master: ${ssim:-unknown}"
    awk -v s="${ssim:-0}" -v m="$MOBILE_MIN_SSIM" 'BEGIN { exit !(s >= m) }' \
        || die "hero-mobile.mp4 SSIM ${ssim:-?} < $MOBILE_MIN_SSIM (looks blocky). Use calmer shots or a shorter cut."
    # Frames for a visual check of the most compressed file.
    local t
    for t in 2 8 14 20; do
        ffmpeg -v error -y -ss "$t" -i "$WORK_DIR/hero-mobile.mp4" -frames:v 1 -update 1 -q:v 2 "$REVIEW_DIR/mobile-check-${t}s.jpg" || true
    done

    # 6. Poster: the first frame, best JPEG quality under 150 KB.
    ok=0
    local q
    for q in 3 4 5 6 7 8 10 12; do
        ffmpeg -v error -y -i "$master" -frames:v 1 -update 1 -q:v "$q" "$WORK_DIR/poster.jpg"
        size="$(size_of "$WORK_DIR/poster.jpg")"
        if [ "$size" -le "$POSTER_MAX_BYTES" ]; then ok=1; break; fi
    done
    [ "$ok" = 1 ] || die "poster.jpg is over 150 KB even at q $q."
    echo "poster.jpg        q $q  $(human "$size")"

    # 7. Every check passed: publish.
    local f
    for f in hero-desktop.mp4 hero-desktop.webm hero-mobile.mp4 poster.jpg; do
        mv -f "$WORK_DIR/$f" "$OUT_DIR/$f"
    done
    echo
    echo "Published to $OUT_DIR/:"
    for f in hero-desktop.mp4 hero-desktop.webm hero-mobile.mp4 poster.jpg; do
        printf '  %-18s %s\n' "$f" "$(human "$(size_of "$OUT_DIR/$f")")"
    done
    echo "Mobile check frames: $REVIEW_DIR/mobile-check-*.jpg"
    echo "Next: php artisan view:clear (not required, URLs are versioned by file time)."
}

case "${1:-}" in
    inspect) shift; cmd_inspect "$@" ;;
    build) cmd_build ;;
    *) sed -n '2,24p' "$0" | sed 's/^# \{0,1\}//'; exit 1 ;;
esac
