#!/usr/bin/env python3

# Modified from: https://github.com/faensi/svg_to_png-converter
# Author: faensi, modified by Hantong Chen
# License: MIT
# Usage: python helper.py -i ./OpenList.svg -s 16 24 32 36 48 64 96 128 256 320 512  --webp --overwrite

import os
import argparse
import cairosvg
from PIL import Image
import io

def generate_icons(input_svg, sizes, output_dir, overwrite=False, generate_webp=False):
    for size in sizes:
        output_png = os.path.join(output_dir, f'{size}x{size}.png')
        if not os.path.exists(output_png) or overwrite:
            cairosvg.svg2png(url=input_svg, write_to=output_png, output_width=size, output_height=size)
            print(f'Generated {output_png}')
        else:
            print(f'Skipped {output_png}, already exists.')
        
        if generate_webp:
            output_webp = os.path.join(output_dir, f'{size}x{size}.webp')
            if not os.path.exists(output_webp) or overwrite:
                if os.path.exists(output_png):
                    with Image.open(output_png) as img:
                        img.save(output_webp, 'WEBP', quality=100)
                    print(f'Generated {output_webp}')
                else:
                    png_data = cairosvg.svg2png(url=input_svg, output_width=size, output_height=size)
                    img = Image.open(io.BytesIO(png_data))
                    img.save(output_webp, 'WEBP', quality=100)
                    print(f'Generated {output_webp}')
            else:
                print(f'Skipped {output_webp}, already exists.')

def main():
    parser = argparse.ArgumentParser(description='Convert SVG file to PNG and WebP icons of specified sizes.')
    parser.add_argument('-i', '--input', required=True, help='Input SVG file path.')
    parser.add_argument('-o', '--output_dir', default='icons', help='Output directory for icon files.')
    parser.add_argument('-s', '--sizes', nargs='+', type=int, default=[16, 32, 64, 128, 256],
                        help='List of icon sizes to generate.')
    parser.add_argument('--overwrite', action='store_true', help='Overwrite existing files.')
    parser.add_argument('--webp', action='store_true', help='Generate WebP format in addition to PNG.')
    
    args = parser.parse_args()
    
    input_svg = args.input
    output_dir = args.output_dir
    sizes = args.sizes
    overwrite = args.overwrite
    generate_webp = args.webp
    
    if not os.path.exists(input_svg):
        print(f'Error: Input file {input_svg} does not exist.')
        return

    if not input_svg.lower().endswith('.svg'):
        print(f'Error: Input file must be an SVG file.')
        return
    
    if not os.path.exists(output_dir):
        os.makedirs(output_dir)
    
    generate_icons(input_svg, sizes, output_dir, overwrite, generate_webp)

if __name__ == "__main__":
    main()