import * as globals from '../app.globals';

export class Link {
  classes: string;
  zIndex: string;
  zIndexOrigin: string;
  top: string;
  left: string;
  width: string;
  height: string;
  path: string;
  strokeColor: string;
  strokeColorHover: string;
  fillColor: string;
  fillColorHover: string;
  strokeWidth: number;
  strokeDasharray: string;
  fill: string;
  parent: number;
  child: number;
  score?: number;
  xBox?: number;
  yBox?: number;
  xText?: number;
  yText?: number;
  xTriangle?: number;
  yTriangle?: number;
  showScore?: boolean;
  hoverSVG?: boolean;

  public constructor() {
    this.showScore = false;
    this.fill = 'none';
    this.strokeWidth = globals.linkWidth;
    this.strokeColor = globals.strokeColor;
    this.strokeColorHover = globals.strokeColorHover;
    this.fillColor = globals.fillColor;
    this.fillColorHover = globals.fillColorHover;
    this.strokeDasharray = globals.strokeDasharray;
    this.hoverSVG = false;
  }
}
