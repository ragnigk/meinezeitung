require 'rho/rhocontroller'
require 'helpers/browser_helper'

class EventController < Rho::RhoController
  include BrowserHelper

  # GET /Event
  def index    
    self.list
  end

  def list
    @events = Event.find_all(:order => 'datum',:orderdir => 'ASC' )
    if @events.empty? then
      self.update
    else
      $tag = @events.first.inserted.to_i
      t = Time.now
      $vtag = t.strftime("%Y%m%d").to_i
      if $tag < $vtag  then    
        Event.delete_all    
        WebView.navigate ( url_for :action => :index )
      end
      render :action => :index, :back => :exit
    end
  end

  def update      
    url = 'http://service.tagesspiegel.de/dishes/mobile2/india.php'
    Rho::AsyncHttp.get(
        :url => url,
        :callback => (url_for :action => :httpget_callback),
        :callback_param => "" )
    render :action => :wait, :back => :exit
  end

  def httpget_callback
    if @params['status'] != 'ok'
      @@error_params = @params
      WebView.navigate (url_for :action => :show_error)
    else
      begin
        require 'json'
        t = Time.now
        $httpresult = @params['body']
        @@get_result = Rho::JSON.parse($httpresult)
        @@get_result.each do |e|
          Event.create(
            :datum => e['datum'],
            :veranstalter => e['veranstalter'],
            :titel => e['titel'],
            :content => e['content'],
            :anschrift => e['anschrift'],
            :verkehr => e['verkehr'],
            :inserted => t.strftime("%Y%m%d").to_i
          )
        end  
        @events = Event.find(:all)
        if @events.empty? then
          WebView.navigate ( url_for :action => :show_error )
        else
          WebView.navigate ( url_for :action => :index )
        end
      rescue Exception => e
        puts "Error: #{e}"
        @@get_result = "Error: #{e}"
      end                        
    end
  end

  def show_error
    render :action => :error, :back => url_for( :action => :index )
  end
  
  def exit
    Rho::RhoApplication.close
    System.exit
  end

  # GET /Event/{1}
  def show
    @event = Event.find(@params['id'])
    if @event
      render :action => :show, :back => url_for(:action => :index)
    else
      redirect :action => :index
    end
  end
end
